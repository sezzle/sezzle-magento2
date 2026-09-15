<?php

namespace Sezzle\Sezzlepay\Test\Unit\Model\Checkout;

use ArrayObject;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sezzle\Sezzlepay\Gateway\Config\Config;
use Sezzle\Sezzlepay\Helper\Data;
use Sezzle\Sezzlepay\Model\Checkout\CheckoutValidator;

/**
 * @covers \Sezzle\Sezzlepay\Model\Checkout\CheckoutValidator
 */
class CheckoutValidatorTest extends TestCase
{
    /**
     * The message CheckoutValidator logs when it fills billing from shipping
     */
    private const COPIED_FROM_SHIPPING =
        'Billing address is empty and not required. Copied from the shipping address.';

    /**
     * @var Data|MockObject
     */
    private $sezzleHelper;

    /**
     * @var Config|MockObject
     */
    private $config;

    /**
     * @var CheckoutValidator
     */
    private $validator;

    /**
     * Backing data for the address doubles, keyed by object id
     *
     * @var ArrayObject[]
     */
    private $addressState = [];

    /**
     * Everything passed to logSezzleActions() during the run
     *
     * @var array
     */
    private $loggedActions = [];

    protected function setUp(): void
    {
        $this->loggedActions = [];
        $this->sezzleHelper = $this->createMock(Data::class);
        $this->sezzleHelper->method('logSezzleActions')->willReturnCallback(
            function ($action) {
                $this->loggedActions[] = $action;
            }
        );
        $this->config = $this->createMock(Config::class);
        $this->validator = new CheckoutValidator($this->sezzleHelper, $this->config);
    }

    /**
     * The default configuration keeps a billing address mandatory, so callers that skip
     * the storefront JS - headless and third party checkouts - must not be handed a
     * relaxed rule they never opted into.
     */
    public function testValidateFailsWhenBillingAddressIsEmptyAndRequired(): void
    {
        $this->config->method('isBillingAddressRequired')->willReturn(true);
        $quote = $this->buildQuote(
            $this->buildAddress([]),
            $this->buildAddress($this->completeAddressData(), Address::TYPE_SHIPPING)
        );

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Please check the billing address');

        $this->validator->validate($quote);
    }

    /**
     * With the requirement turned off, an empty billing address is filled from shipping
     * rather than skipped. Leaving it empty would let the shopper authorize at Sezzle
     * and only then fail, because Magento validates billing during order submission.
     */
    public function testEmptyBillingAddressIsCopiedFromShippingWhenNotRequired(): void
    {
        $this->config->method('isBillingAddressRequired')->willReturn(false);

        $billing = $this->buildAddress([]);
        $shipping = $this->buildAddress($this->completeAddressData(), Address::TYPE_SHIPPING);
        $exported = $this->createMock(AddressInterface::class);
        $shipping->method('exportCustomerAddress')->willReturn($exported);

        $billing->expects($this->once())
            ->method('importCustomerAddressData')
            ->with($exported)
            ->willReturnCallback(function () use ($billing) {
                $this->fillAddress($billing, $this->completeAddressData());

                return $billing;
            });

        $this->validator->validate($this->buildQuote($billing, $shipping));

        $this->assertSame(1, $this->timesLogged(self::COPIED_FROM_SHIPPING));
        // Both addresses still validated afterwards - the copy is not a way to skip it.
        $this->assertSame(2, $this->timesLogged('Address Validated'));
    }

    /**
     * The quote's own store decides, not whatever scope happens to be current. A webapi
     * request carries no admin scope to fall back on.
     */
    public function testBillingAddressRequirementIsReadForTheQuoteStore(): void
    {
        $this->config->expects($this->once())
            ->method('isBillingAddressRequired')
            ->with(7)
            ->willReturn(true);

        $quote = $this->buildQuote(
            $this->buildAddress([]),
            $this->buildAddress($this->completeAddressData(), Address::TYPE_SHIPPING)
        );
        $quote->method('getStoreId')->willReturn(7);

        $this->expectException(LocalizedException::class);

        $this->validator->validate($quote);
    }

    /**
     * A billing address the shopper did supply is never second guessed, so the setting
     * is not even consulted.
     */
    public function testConfigIsNotConsultedWhenBillingAddressIsPresent(): void
    {
        $this->config->expects($this->never())->method('isBillingAddressRequired');

        $data = $this->completeAddressData();
        $quote = $this->buildQuote($this->buildAddress($data), $this->buildAddress($data, Address::TYPE_SHIPPING));

        $this->validator->validate($quote);

        $this->assertSame(2, $this->timesLogged('Address Validated'));
    }

    /**
     * A config lookup failure keeps the billing address required. Rejecting the session
     * request is recoverable; failing after the shopper has authorized is not.
     */
    public function testEmptyBillingAddressStaysRequiredWhenTheConfigCannotBeRead(): void
    {
        $this->config->method('isBillingAddressRequired')
            ->willThrowException(new NoSuchEntityException(__('no store')));

        $billing = $this->buildAddress([]);
        $billing->expects($this->never())->method('importCustomerAddressData');

        $quote = $this->buildQuote(
            $billing,
            $this->buildAddress($this->completeAddressData(), Address::TYPE_SHIPPING)
        );

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Please check the billing address');

        $this->validator->validate($quote);
    }

    /**
     * A virtual quote has no shipping address to copy from, so core can only validate
     * its billing address at placeOrder() time. Stop the shopper here rather than after
     * they have authorized at Sezzle.
     */
    public function testValidateFailsForVirtualQuoteWithEmptyBillingAddress(): void
    {
        $this->config->method('isBillingAddressRequired')->willReturn(false);
        $quote = $this->buildQuote(
            $this->buildAddress([]),
            $this->buildAddress([], Address::TYPE_SHIPPING),
            true
        );

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Please check the billing address');

        $this->validator->validate($quote);
    }

    public function testValidatePassesForVirtualQuoteWithCompleteBillingAddress(): void
    {
        $this->config->method('isBillingAddressRequired')->willReturn(true);
        $billing = $this->buildAddress($this->completeAddressData());
        $quote = $this->buildQuote($billing, $this->buildAddress([], Address::TYPE_SHIPPING), true);

        $this->validator->validate($quote);

        // One address validated, not two: the shipping address was never looked at.
        $this->assertSame(1, $this->timesLogged('Address Validated'));
    }

    /**
     * A partially filled billing address is still a shopper mistake - it is not empty,
     * so there is nothing to fall back on.
     */
    public function testValidateFailsWhenBillingAddressIsPartiallyFilled(): void
    {
        $this->config->method('isBillingAddressRequired')->willReturn(false);
        $billing = $this->buildAddress(['firstname' => 'Jane', 'lastname' => 'Doe']);
        $billing->expects($this->never())->method('importCustomerAddressData');

        $quote = $this->buildQuote(
            $billing,
            $this->buildAddress($this->completeAddressData(), Address::TYPE_SHIPPING)
        );

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Please check the billing address');

        $this->validator->validate($quote);
    }

    public function testValidatePassesWhenBothAddressesAreComplete(): void
    {
        $this->config->method('isBillingAddressRequired')->willReturn(true);
        $data = $this->completeAddressData();
        $quote = $this->buildQuote($this->buildAddress($data), $this->buildAddress($data, Address::TYPE_SHIPPING));

        $this->validator->validate($quote);

        $this->assertSame(2, $this->timesLogged('Address Validated'));
        $this->assertSame(0, $this->timesLogged(self::COPIED_FROM_SHIPPING));
    }

    /**
     * Country alone does not count as shopper entered data - Magento pre-selects the
     * store default on every untouched address form - so the address is still empty and
     * still eligible to be filled from shipping.
     */
    public function testBillingAddressCarryingOnlyDefaultCountryIsCopiedFromShipping(): void
    {
        $this->config->method('isBillingAddressRequired')->willReturn(false);

        $billing = $this->buildAddress(['country_id' => 'US']);
        $shipping = $this->buildAddress($this->completeAddressData(), Address::TYPE_SHIPPING);
        $shipping->method('exportCustomerAddress')->willReturn($this->createMock(AddressInterface::class));

        $billing->expects($this->once())
            ->method('importCustomerAddressData')
            ->willReturnCallback(function () use ($billing) {
                $this->fillAddress($billing, $this->completeAddressData());

                return $billing;
            });

        $this->validator->validate($this->buildQuote($billing, $shipping));
    }

    /**
     * Shipping is validated before billing is derived from it, so a quote missing both
     * addresses names the one the shopper actually has to fix.
     */
    public function testValidateFailsWhenShippingAddressIsEmpty(): void
    {
        $this->config->method('isBillingAddressRequired')->willReturn(false);
        $quote = $this->buildQuote(
            $this->buildAddress([]),
            $this->buildAddress([], Address::TYPE_SHIPPING)
        );

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Please check the shipping address');

        $this->validator->validate($quote);
    }

    /**
     * How many times a message was logged, whether logged bare or inside an array
     *
     * @param string $message
     * @return int
     */
    private function timesLogged(string $message): int
    {
        $messages = array_map(static function ($action) {
            return is_array($action) ? ($action['message'] ?? '') : (string)$action;
        }, $this->loggedActions);

        return count(array_keys($messages, $message, true));
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
     * getAddressType() is a magic getter, so the type is served through getData(). The
     * backing state is kept aside so fillAddress() can stand in for the copy core would
     * perform inside a stubbed importCustomerAddressData().
     *
     * @param array $data
     * @param string $type
     * @return Address|MockObject
     */
    private function buildAddress(array $data, string $type = Address::TYPE_BILLING)
    {
        $data['address_type'] = $type;
        $state = new ArrayObject($data);

        $address = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData', 'getShippingMethod', 'importCustomerAddressData', 'exportCustomerAddress'])
            ->getMock();

        $address->method('getData')->willReturnCallback(
            static function ($field = null) use ($state) {
                return $field === null
                    ? $state->getArrayCopy()
                    : ($state->offsetExists($field) ? $state->offsetGet($field) : null);
            }
        );
        $address->method('getShippingMethod')->willReturn('flatrate_flatrate');

        $this->addressState[spl_object_id($address)] = $state;

        return $address;
    }

    /**
     * Apply address data to a double built by buildAddress()
     *
     * @param Address|MockObject $address
     * @param array $data
     * @return void
     */
    private function fillAddress($address, array $data): void
    {
        $state = $this->addressState[spl_object_id($address)];
        foreach ($data as $field => $value) {
            $state->offsetSet($field, $value);
        }
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
            ->onlyMethods(['getBillingAddress', 'getShippingAddress', 'isVirtual', 'getStoreId', 'getId'])
            ->getMock();

        $quote->method('getBillingAddress')->willReturn($billingAddress);
        $quote->method('getShippingAddress')->willReturn($shippingAddress);
        $quote->method('isVirtual')->willReturn($isVirtual);
        $quote->method('getId')->willReturn(1);

        return $quote;
    }
}
