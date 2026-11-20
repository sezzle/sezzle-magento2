<?php

namespace Sezzle\Sezzlepay\Test\Unit\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sezzle\Sezzlepay\Gateway\Config\Config;
use Sezzle\Sezzlepay\Model\StoreConfigResolver;

/**
 * @covers \Sezzle\Sezzlepay\Gateway\Config\Config::getSezzleAdsMargin
 */
class ConfigTest extends TestCase
{
    #[DataProvider('marginCases')]
    public function testSezzleAdsMargin(?string $stored, string $expected): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturn($stored);

        $config = new Config(
            $this->createMock(StoreConfigResolver::class),
            $scopeConfig,
            $this->createMock(UrlInterface::class),
            $this->createMock(Resolver::class),
            'sezzlepay'
        );

        $this->assertSame($expected, $config->getSezzleAdsMargin(1));
    }

    public static function marginCases(): array
    {
        return [
            'unset' => [null, ''],
            'shorthand' => ['24px 0', '24px 0'],
            // Imported or written directly, bypassing the admin backend's normalization.
            'newline inside' => ["20px\n0", '20px 0'],
            'tabs and padding' => ["\t16px \t 0  32px\r\n", '16px 0 32px'],
            'not a margin' => ["0;}</style>", ''],
        ];
    }
}
