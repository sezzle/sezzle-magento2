<?php

namespace Sezzle\Sezzlepay\Test\Unit\Model\Checkout;

use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sezzle\Sezzlepay\Helper\Data;
use Sezzle\Sezzlepay\Model\Checkout\CheckoutValidator;

/**
 * @covers \Sezzle\Sezzlepay\Model\Checkout\CheckoutValidator
 */
class CheckoutValidatorTest extends TestCase
{
    /**
     * @var Data|MockObject
     */
    private $sezzleHelper;

    /**
     * @var CheckoutValidator
     */
    private $validator;

    protected function setUp(): void
    {
        $this->sezzleHelper = $this->createMock(Data::class);
        $this->validator = new CheckoutValidator($this->sezzleHelper);
    }

    /**
     * A blank billing address is the shopper clearing "same as shipping" and leaving
     * the form untouched. That must not block the Sezzle session request.
     */
    public function testValidatePassesWhenBillingAddressIsEmpty(): void
    {
        $quote = $this->buildQuote($this->buildAddress([]), $this->buildAddress($this->completeAddressData(), Address::TYPE_SHIPPING));

        $this->validator->validate($quote);

        $this->assertTrue(true, 'No exception is thrown for an empty billing address.');
    }

    /**
     * A virtual quote has no shipping address, so its billing address is the only one
     * core can validate at placeOrder() time. Stop the shopper here rather than after
     * they have authorized at Sezzle.
     */
    public function testValidateFailsForVirtualQuoteWithEmptyBillingAddress(): void
    {
        $quote = $this->buildQuote($this->buildAddress([]), $this->buildAddress([], Address::TYPE_SHIPPING), true);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Please check the billing address');

        $this->validator->validate($quote);
    }

    public function testValidatePassesForVirtualQuoteWithCompleteBillingAddress(): void
    {
        $billing = $this->buildAddress($this->completeAddressData());
        $quote = $this->buildQuote($billing, $this->buildAddress([], Address::TYPE_SHIPPING), true);

        $this->validator->validate($quote);

        $this->assertTrue(true, 'A virtual quote only needs a billing address.');
    }

    /**
     * A partially filled billing address is still a shopper mistake.
     */
    public function testValidateFailsWhenBillingAddressIsPartiallyFilled(): void
    {
        $billing = $this->buildAddress(['firstname' => 'Jane', 'lastname' => 'Doe']);
        $quote = $this->buildQuote($billing, $this->buildAddress($this->completeAddressData(), Address::TYPE_SHIPPING));

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Please check the billing address');

        $this->validator->validate($quote);
    }

    public function testValidatePassesWhenBothAddressesAreComplete(): void
    {
        $data = $this->completeAddressData();
        $quote = $this->buildQuote($this->buildAddress($data), $this->buildAddress($data, Address::TYPE_SHIPPING));

        $this->validator->validate($quote);

        $this->assertTrue(true, 'No exception is thrown for complete addresses.');
    }

    /**
     * Country alone does not count as shopper entered data - Magento pre-selects the
     * store default on every untouched address form.
     */
    public function testValidatePassesWhenBillingAddressOnlyCarriesDefaultCountry(): void
    {
        $billing = $this->buildAddress(['country_id' => 'US']);
        $quote = $this->buildQuote($billing, $this->buildAddress($this->completeAddressData(), Address::TYPE_SHIPPING));

        $this->validator->validate($quote);

        $this->assertTrue(true, 'No exception is thrown for a country-only billing address.');
    }

    public function testValidateFailsWhenShippingAddressIsEmpty(): void
    {
        $quote = $this->buildQuote($this->buildAddress($this->completeAddressData()), $this->buildAddress([], Address::TYPE_SHIPPING));

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Please check the shipping address');

        $this->validator->validate($quote);
    }

    /**
     * @return array
     */
    private function completeAddressData(): array
    {
        return [
            'firstname' => 'Jane',
            'lastname' => 'Doe',
            'street' => ['123 Main St'],
            'city' => 'Minneapolis',
            'region_id' => 33,
            'postcode' => '55401',
            'country_id' => 'US',
            'telephone' => '5551234567'
        ];
    }

    /**
     * getAddressType() is a magic getter, so the type is served through getData().
     *
     * @param array $data
     * @param string $type
     * @return Address|MockObject
     */
    private function buildAddress(array $data, string $type = Address::TYPE_BILLING)
    {
        $data['address_type'] = $type;

        $address = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData', 'getShippingMethod'])
            ->getMock();

        $address->method('getData')->willReturnCallback(
            static function ($field = null) use ($data) {
                return $field === null ? $data : ($data[$field] ?? null);
            }
        );
        $address->method('getShippingMethod')->willReturn('flatrate_flatrate');

        return $address;
    }

    /**
     * @param Address|MockObject $billingAddress
     * @param Address|MockObject $shippingAddress
     * @param bool $isVirtual
     * @return Quote|MockObject
     */
    private function buildQuote($billingAddress, $shippingAddress, bool $isVirtual = false)
    {
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getBillingAddress', 'getShippingAddress', 'isVirtual'])
            ->getMock();

        $quote->method('getBillingAddress')->willReturn($billingAddress);
        $quote->method('getShippingAddress')->willReturn($shippingAddress);
        $quote->method('isVirtual')->willReturn($isVirtual);

        return $quote;
    }
}
