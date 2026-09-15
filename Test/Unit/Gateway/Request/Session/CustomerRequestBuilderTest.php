<?php

namespace Sezzle\Sezzlepay\Test\Unit\Gateway\Request\Session;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sezzle\Sezzlepay\Gateway\Config\Config;
use Sezzle\Sezzlepay\Gateway\Request\Session\CustomerRequestBuilder;

/**
 * @covers \Sezzle\Sezzlepay\Gateway\Request\Session\CustomerRequestBuilder
 */
class CustomerRequestBuilderTest extends TestCase
{
    /**
     * @var Config|MockObject
     */
    private $config;

    /**
     * @var CustomerRequestBuilder
     */
    private $builder;

    protected function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
        $this->builder = new CustomerRequestBuilder($this->config);
    }

    /**
     * A billing address the shopper supplied is sent as its own payload and is the
     * identity Sezzle sees.
     */
    public function testBillingAddressIsSentWhenPresent(): void
    {
        $billing = $this->buildAddress($this->addressData('Jane', 'Doe', '5551110000'));
        $shipping = $this->buildAddress($this->addressData('Sam', 'Shipper', '5552220000'), Address::TYPE_SHIPPING);

        $customer = $this->build($billing, $shipping)['customer'];

        $this->assertArrayHasKey('billing_address', $customer);
        $this->assertSame('5551110000', $customer['phone'], 'Identity comes from billing when it exists.');
        $this->assertSame('Jane', $customer['first_name']);
        $this->assertSame('Doe', $customer['last_name']);
    }

    /**
     * With no billing address there is nothing to send, and blanks would be worse than
     * the omission. Identity falls back to shipping rather than going out empty.
     */
    public function testEmptyBillingAddressIsOmittedAndIdentityFallsBackToShipping(): void
    {
        $billing = $this->buildAddress([]);
        $shipping = $this->buildAddress($this->addressData('Sam', 'Shipper', '5552220000'), Address::TYPE_SHIPPING);

        $customer = $this->build($billing, $shipping)['customer'];

        $this->assertArrayNotHasKey('billing_address', $customer);
        $this->assertSame('5552220000', $customer['phone']);
        $this->assertSame('Sam', $customer['first_name']);
        $this->assertSame('Shipper', $customer['last_name']);
    }

    /**
     * Country alone is not shopper data, so such an address is still treated as absent.
     * This is the same rule CheckoutValidator applies before filling billing in.
     */
    public function testBillingAddressCarryingOnlyDefaultCountryIsTreatedAsAbsent(): void
    {
        $billing = $this->buildAddress(['country_id' => 'US']);
        $shipping = $this->buildAddress($this->addressData('Sam', 'Shipper', '5552220000'), Address::TYPE_SHIPPING);

        $customer = $this->build($billing, $shipping)['customer'];

        $this->assertArrayNotHasKey('billing_address', $customer);
        $this->assertSame('5552220000', $customer['phone']);
    }

    /**
     * The quote's own customer name wins over whatever the address carries.
     */
    public function testQuoteCustomerNameTakesPrecedenceOverTheAddress(): void
    {
        $billing = $this->buildAddress($this->addressData('Jane', 'Doe', '5551110000'));
        $shipping = $this->buildAddress($this->addressData('Sam', 'Shipper', '5552220000'), Address::TYPE_SHIPPING);

        $customer = $this->build($billing, $shipping, 'Quoted', 'Name')['customer'];

        $this->assertSame('Quoted', $customer['first_name']);
        $this->assertSame('Name', $customer['last_name']);
    }

    /**
     * The shipping address is always sent, whether or not billing accompanies it.
     */
    public function testShippingAddressPayloadIsAlwaysBuilt(): void
    {
        $shipping = $this->buildAddress($this->addressData('Sam', 'Shipper', '5552220000'), Address::TYPE_SHIPPING);

        $customer = $this->build($this->buildAddress([]), $shipping)['customer'];

        $this->assertSame(
            [
                'name' => 'Sam Shipper',
                'street' => '123 Main St',
                'street2' => 'Apt 4',
                'city' => 'Minneapolis',
                'state' => 'MN',
                'postal_code' => '55401',
                'country_code' => 'US',
                'phone' => '5552220000'
            ],
            $customer['shipping_address']
        );
    }

    public function testTokenizeIsOnWhenEnabledAndNotInContext(): void
    {
        $this->config->method('isInContextModeActive')->willReturn(false);
        $this->config->method('isTokenizationEnabled')->willReturn(true);

        $this->assertTrue($this->buildBareCustomer()['tokenize']);
    }

    /**
     * In context checkout never tokenizes, whatever the tokenization setting says.
     */
    public function testTokenizeIsOffInContextEvenWhenEnabled(): void
    {
        $this->config->method('isInContextModeActive')->willReturn(true);
        $this->config->method('isTokenizationEnabled')->willReturn(true);

        $this->assertFalse($this->buildBareCustomer()['tokenize']);
    }

    public function testTokenizeIsOffWhenDisabled(): void
    {
        $this->config->method('isInContextModeActive')->willReturn(false);
        $this->config->method('isTokenizationEnabled')->willReturn(false);

        $this->assertFalse($this->buildBareCustomer()['tokenize']);
    }

    /**
     * A config lookup failure must not take the session request down with it.
     */
    public function testTokenizeFallsBackToFalseWhenTheConfigCannotBeRead(): void
    {
        $this->config->method('isInContextModeActive')
            ->willThrowException(new NoSuchEntityException(__('no store')));

        $this->assertFalse($this->buildBareCustomer()['tokenize']);
    }

    /**
     * Build with both addresses blank, for cases that only care about the flags
     *
     * @return array
     */
    private function buildBareCustomer(): array
    {
        return $this->build(
            $this->buildAddress([]),
            $this->buildAddress([], Address::TYPE_SHIPPING)
        )['customer'];
    }

    /**
     * @param Address|MockObject $billing
     * @param Address|MockObject $shipping
     * @param string|null $customerFirstname
     * @param string|null $customerLastname
     * @return array
     */
    private function build($billing, $shipping, $customerFirstname = null, $customerLastname = null): array
    {
        $customerData = $this->createMock(CustomerInterface::class);
        $customerData->method('getDob')->willReturn('1990-01-01');

        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getBillingAddress', 'getShippingAddress', 'getCustomer'])
            ->getMock();

        $quote->method('getBillingAddress')->willReturn($billing);
        $quote->method('getShippingAddress')->willReturn($shipping);
        $quote->method('getCustomer')->willReturn($customerData);

        // getCustomerEmail() and the name getters are DataObject magic rather than real
        // methods, so they cannot be stubbed - seed the data they read instead.
        $quote->setData('customer_email', 'shopper@example.com');
        $quote->setData('customer_firstname', $customerFirstname);
        $quote->setData('customer_lastname', $customerLastname);

        return $this->builder->build(['quote' => $quote]);
    }

    /**
     * @param string $firstname
     * @param string $lastname
     * @param string $telephone
     * @return array
     */
    private function addressData(string $firstname, string $lastname, string $telephone): array
    {
        return [
            'firstname' => $firstname,
            'lastname' => $lastname,
            'street' => ['123 Main St', 'Apt 4'],
            'city' => 'Minneapolis',
            'region_id' => 33,
            'region_code' => 'MN',
            'postcode' => '55401',
            'country_id' => 'US',
            'telephone' => $telephone
        ];
    }

    /**
     * Util::isAddressEmpty() reads through getData(), while the payload is built from
     * the typed getters, so the double has to answer both from the same state.
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
            ->onlyMethods([
                'getData',
                'getName',
                'getStreetLine',
                'getCity',
                'getRegionCode',
                'getPostcode',
                'getCountryId',
                'getTelephone',
                'getFirstname',
                'getLastname'
            ])
            ->getMock();

        $address->method('getData')->willReturnCallback(
            static function ($field = null) use ($data) {
                return $field === null ? $data : ($data[$field] ?? null);
            }
        );
        $address->method('getName')->willReturn(
            trim(($data['firstname'] ?? '') . ' ' . ($data['lastname'] ?? ''))
        );
        $address->method('getStreetLine')->willReturnCallback(
            static function ($number) use ($data) {
                return $data['street'][$number - 1] ?? '';
            }
        );
        $address->method('getCity')->willReturn($data['city'] ?? null);
        $address->method('getRegionCode')->willReturn($data['region_code'] ?? null);
        $address->method('getPostcode')->willReturn($data['postcode'] ?? null);
        $address->method('getCountryId')->willReturn($data['country_id'] ?? null);
        $address->method('getTelephone')->willReturn($data['telephone'] ?? null);
        $address->method('getFirstname')->willReturn($data['firstname'] ?? null);
        $address->method('getLastname')->willReturn($data['lastname'] ?? null);

        return $address;
    }
}
