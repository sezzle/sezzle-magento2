<?php

namespace Sezzle\Sezzlepay\Test\Unit\Block\Widget;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\Serialize\Serializer\JsonHexTag;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sezzle\Sezzlepay\Block\Widget\SezzleAds;
use Sezzle\Sezzlepay\Gateway\Config\Config;
use Sezzle\Sezzlepay\Helper\Data as Helper;

/**
 * @covers \Sezzle\Sezzlepay\Block\Widget\SezzleAds
 */
class SezzleAdsTest extends TestCase
{
    private const MERCHANT_UUID = 'bea82572-6387-4b7f-81c9-4b92854316c6';

    /**
     * @var Config|MockObject
     */
    private $config;

    /**
     * @var SezzleAds
     */
    private $block;

    protected function setUp(): void
    {
        $this->config = $this->createMock(Config::class);

        // The block's parents fetch these from the global object manager.
        (new ObjectManager($this))->prepareObjectManager([
            [JsonHelper::class, $this->createMock(JsonHelper::class)],
            [DirectoryHelper::class, $this->createMock(DirectoryHelper::class)],
            [SecureHtmlRenderer::class, $this->createMock(SecureHtmlRenderer::class)],
        ]);

        $this->block = new SezzleAds(
            $this->createMock(Context::class),
            $this->config,
            $this->createMock(CheckoutSession::class),
            $this->createMock(JsonHexTag::class),
            $this->createMock(Helper::class)
        );
    }

    /**
     * The ad promotes the Sezzle app, not paying with Sezzle here, so the payment method being off does not hide it.
     */
    #[DataProvider('paymentMethodStates')]
    public function testRendersWhateverThePaymentMethodState(bool $paymentEnabled): void
    {
        $this->config->method('isEnabled')->willReturn($paymentEnabled);
        $this->config->method('isSezzleAdsEnabled')->willReturn(true);
        $this->config->method('getMerchantUUID')->willReturn(self::MERCHANT_UUID);

        $this->assertTrue($this->block->isSezzleAdsEnabled());
    }

    public static function paymentMethodStates(): array
    {
        return ['payment method enabled' => [true], 'payment method disabled' => [false]];
    }

    public function testDoesNotRenderWhenSezzleAdsIsOff(): void
    {
        $this->config->method('isSezzleAdsEnabled')->willReturn(false);
        $this->config->method('getMerchantUUID')->willReturn(self::MERCHANT_UUID);

        $this->assertFalse($this->block->isSezzleAdsEnabled());
    }

    public function testDoesNotRenderWithoutAMerchantUUID(): void
    {
        $this->config->method('isSezzleAdsEnabled')->willReturn(true);
        $this->config->method('getMerchantUUID')->willReturn(null);

        $this->assertFalse($this->block->isSezzleAdsEnabled());
    }

    public function testDoesNotRenderWhenTheConfigCannotBeRead(): void
    {
        $this->config->method('isSezzleAdsEnabled')
            ->willThrowException(new NoSuchEntityException());

        $this->assertFalse($this->block->isSezzleAdsEnabled());
    }
}
