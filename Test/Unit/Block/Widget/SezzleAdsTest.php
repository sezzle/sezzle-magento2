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
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Sezzle\Sezzlepay\Block\Widget\SezzleAds;
use Sezzle\Sezzlepay\Gateway\Command\AuthorizeCommand;
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
     * @var CheckoutSession|MockObject
     */
    private $checkoutSession;

    /**
     * @var SezzleAds
     */
    private $block;

    protected function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
        $this->checkoutSession = $this->createMock(CheckoutSession::class);

        // The block's parents fetch these from the global object manager.
        (new ObjectManager($this))->prepareObjectManager([
            [JsonHelper::class, $this->createMock(JsonHelper::class)],
            [DirectoryHelper::class, $this->createMock(DirectoryHelper::class)],
            [SecureHtmlRenderer::class, $this->createMock(SecureHtmlRenderer::class)],
        ]);

        $this->block = new SezzleAds(
            $this->createMock(Context::class),
            $this->config,
            $this->checkoutSession,
            new JsonHexTag(),
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

    /**
     * The order UUID is passed only for an order paid with Sezzle that has one, so the ad's copy can refer to it.
     */
    #[DataProvider('orderUUIDCases')]
    public function testSdkConfigCarriesTheSezzleOrderUUID(?string $method, $storedUUID, ?string $expected): void
    {
        $this->config->method('getMerchantUUID')->willReturn(self::MERCHANT_UUID);
        $this->config->method('getSezzleAdsTheme')->willReturn('light');

        $payment = null;
        if ($method !== null) {
            $payment = $this->createMock(Payment::class);
            $payment->method('getMethod')->willReturn($method);
            $payment->method('getAdditionalInformation')
                ->with(AuthorizeCommand::KEY_ORIGINAL_ORDER_UUID)
                ->willReturn($storedUUID);
        }
        $order = $this->createMock(Order::class);
        $order->method('getPayment')->willReturn($payment);
        $this->checkoutSession->method('getLastRealOrder')->willReturn($order);

        $expectedConfig = ['merchantUUID' => self::MERCHANT_UUID, 'theme' => 'light'];
        if ($expected !== null) {
            $expectedConfig['orderUUID'] = $expected;
        }
        $this->assertSame($expectedConfig, json_decode($this->block->getSdkConfigJson(), true));
    }

    public static function orderUUIDCases(): array
    {
        $orderUUID = '5d6ba1a8-2b4f-4c4e-9b8e-0f6f1c1e2d3a';

        return [
            'sezzle order with a UUID' => ['sezzlepay', $orderUUID, $orderUUID],
            'sezzle order with an empty UUID' => ['sezzlepay', '', null],
            'sezzle order without a UUID' => ['sezzlepay', null, null],
            'another payment method' => ['checkmo', $orderUUID, null],
            'no payment' => [null, null, null],
        ];
    }

    public function testOnlyTheConfiguredPlacementMatches(): void
    {
        $this->config->method('getSezzleAdsPlacement')->willReturn('main_top');

        foreach (['main_top', 'main_bottom', 'after_confirmation'] as $placement) {
            $this->block->setData('placement', $placement);
            $this->assertSame($placement === 'main_top', $this->block->isConfiguredPlacement(), $placement);
        }
    }
}
