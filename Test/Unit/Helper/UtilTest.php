<?php

namespace Sezzle\Sezzlepay\Test\Unit\Helper;

use Magento\Quote\Model\Quote\Address;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sezzle\Sezzlepay\Helper\Util;
use stdClass;

/**
 * @covers \Sezzle\Sezzlepay\Helper\Util
 */
class UtilTest extends TestCase
{
    /**
     * Callers reach for this before they know whether an address exists at all, so
     * anything that is not a quote address holds no shopper data by definition.
     */
    public function testNullIsEmpty(): void
    {
        $this->assertTrue(Util::isAddressEmpty(null));
    }

    public function testNoArgumentIsEmpty(): void
    {
        $this->assertTrue(Util::isAddressEmpty());
    }

    public function testUnrelatedObjectIsEmpty(): void
    {
        $this->assertTrue(Util::isAddressEmpty(new stdClass()));
    }

    public function testStringIsEmpty(): void
    {
        $this->assertTrue(Util::isAddressEmpty('123 Main St'));
    }

    public function testUntouchedAddressIsEmpty(): void
    {
        $this->assertTrue(Util::isAddressEmpty($this->buildAddress([])));
    }

    /**
     * Magento pre-selects the store default country on every address form, so a
     * country is not evidence the shopper typed anything.
     */
    public function testCountryAloneIsEmpty(): void
    {
        $this->assertTrue(Util::isAddressEmpty($this->buildAddress(['country_id' => 'US'])));
    }

    public function testBlankStreetLinesAreEmpty(): void
    {
        $this->assertTrue(Util::isAddressEmpty($this->buildAddress(['street' => ['', '']])));
    }

    public function testWhitespaceIsEmpty(): void
    {
        $address = $this->buildAddress(['firstname' => '   ', 'city' => "\t"]);

        $this->assertTrue(Util::isAddressEmpty($address));
    }

    public function testEmptyStringsAreEmpty(): void
    {
        $address = $this->buildAddress(['firstname' => '', 'postcode' => '']);

        $this->assertTrue(Util::isAddressEmpty($address));
    }

    public function testASingleFieldIsEnoughToCount(): void
    {
        $this->assertFalse(Util::isAddressEmpty($this->buildAddress(['firstname' => 'Jane'])));
    }

    /**
     * Street arrives as an array, so it is joined before being judged.
     */
    public function testStreetLineCounts(): void
    {
        $this->assertFalse(Util::isAddressEmpty($this->buildAddress(['street' => ['123 Main St']])));
    }

    public function testLaterStreetLineCounts(): void
    {
        $this->assertFalse(Util::isAddressEmpty($this->buildAddress(['street' => ['', 'Apt 4']])));
    }

    /**
     * A numeric field is still shopper data once cast to string.
     */
    public function testRegionIdCounts(): void
    {
        $this->assertFalse(Util::isAddressEmpty($this->buildAddress(['region_id' => 33])));
    }

    public function testCompanyCounts(): void
    {
        $this->assertFalse(Util::isAddressEmpty($this->buildAddress(['company' => 'Sezzle'])));
    }

    public function testVatIdCounts(): void
    {
        $this->assertFalse(Util::isAddressEmpty($this->buildAddress(['vat_id' => 'GB123'])));
    }

    /**
     * The ignored country must not mask a field that does count.
     */
    public function testCountryAlongsideRealDataCounts(): void
    {
        $address = $this->buildAddress(['country_id' => 'US', 'city' => 'Minneapolis']);

        $this->assertFalse(Util::isAddressEmpty($address));
    }

    /**
     * @param array $data
     * @return Address|MockObject
     */
    private function buildAddress(array $data)
    {
        $address = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData'])
            ->getMock();

        $address->method('getData')->willReturnCallback(
            static function ($field = null) use ($data) {
                return $field === null ? $data : ($data[$field] ?? null);
            }
        );

        return $address;
    }
}
