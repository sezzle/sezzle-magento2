<?php

namespace Sezzle\Sezzlepay\Test\Unit\Plugin\Checkout\Block\Checkout;

use Magento\Checkout\Block\Checkout\LayoutProcessor;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sezzle\Sezzlepay\Gateway\Config\Config;
use Sezzle\Sezzlepay\Helper\Data;
use Sezzle\Sezzlepay\Plugin\Checkout\Block\Checkout\LayoutProcessorPlugin;

/**
 * @covers \Sezzle\Sezzlepay\Plugin\Checkout\Block\Checkout\LayoutProcessorPlugin
 */
class LayoutProcessorPluginTest extends TestCase
{
    /**
     * Spelled out again rather than imported from the plugin.
     *
     * The plugin holds the only copy it uses, so a typo there no longer makes it write
     * somewhere it never reads. What still has to be caught is a typo in that one copy,
     * and a fixture built from the same constant would agree with any path at all. This
     * is the oracle, so it has to be written independently.
     */
    private const LAYOUT_PATH = 'components/checkout/children/steps/children/billing-step/'
        . 'children/payment/children/renders/children/sezzlepay/methods/sezzlepay/'
        . 'isBillingAddressRequired';

    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @var Config|MockObject
     */
    private $config;

    /**
     * @var CheckoutSession|MockObject
     */
    private $checkoutSession;

    /**
     * @var LayoutProcessor|MockObject
     */
    private $subject;

    /**
     * @var LayoutProcessorPlugin
     */
    private $plugin;

    protected function setUp(): void
    {
        $this->arrayManager = new ArrayManager();
        $this->config = $this->createMock(Config::class);
        $this->checkoutSession = $this->getMockBuilder(CheckoutSession::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getQuote'])
            ->getMock();
        $this->subject = $this->createMock(LayoutProcessor::class);

        $this->plugin = new LayoutProcessorPlugin(
            $this->config,
            $this->checkoutSession,
            $this->createMock(Data::class),
            $this->arrayManager
        );
    }

    public function testFlagIsLoweredWhenBillingAddressIsNotRequired(): void
    {
        $this->config->method('isBillingAddressRequired')->willReturn(false);
        $this->checkoutSession->method('getQuote')->willReturn($this->buildQuote(false));

        $result = $this->plugin->beforeProcess($this->subject, $this->buildLayout());

        $this->assertFalse($this->readFlag($result[0]));
    }

    public function testFlagIsLeftAloneWhenBillingAddressIsRequired(): void
    {
        $this->config->method('isBillingAddressRequired')->willReturn(true);

        $result = $this->plugin->beforeProcess($this->subject, $this->buildLayout());

        $this->assertTrue($this->readFlag($result[0]));
    }

    /**
     * A virtual quote has no shipping address to reuse, so dropping its billing form
     * would leave the shopper unable to supply one at all.
     */
    public function testFlagIsLeftAloneForVirtualQuote(): void
    {
        $this->config->method('isBillingAddressRequired')->willReturn(false);
        $this->checkoutSession->method('getQuote')->willReturn($this->buildQuote(true));

        $result = $this->plugin->beforeProcess($this->subject, $this->buildLayout());

        $this->assertTrue($this->readFlag($result[0]));
    }

    public function testLayoutWithoutSezzleNodeIsPassedThroughUntouched(): void
    {
        $this->config->method('isBillingAddressRequired')->willReturn(false);
        $this->checkoutSession->method('getQuote')->willReturn($this->buildQuote(false));

        $result = $this->plugin->beforeProcess($this->subject, ['components' => []]);

        $this->assertSame(['components' => []], $result[0]);
    }

    /**
     * A broken session must not take the checkout page down with it.
     */
    public function testFlagIsLeftAloneWhenTheQuoteCannotBeResolved(): void
    {
        $this->config->method('isBillingAddressRequired')->willReturn(false);
        $this->checkoutSession->method('getQuote')->willThrowException(new \Exception('no session'));

        $result = $this->plugin->beforeProcess($this->subject, $this->buildLayout());

        $this->assertTrue($this->readFlag($result[0]));
    }

    /**
     * @param bool $isVirtual
     * @return Quote|MockObject
     */
    private function buildQuote(bool $isVirtual)
    {
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isVirtual'])
            ->getMock();
        $quote->method('isVirtual')->willReturn($isVirtual);

        return $quote;
    }

    /**
     * @return array
     */
    private function buildLayout(): array
    {
        return $this->arrayManager->set(self::LAYOUT_PATH, [], true);
    }

    /**
     * @param array $jsLayout
     * @return bool
     */
    private function readFlag(array $jsLayout): bool
    {
        return $this->arrayManager->get(self::LAYOUT_PATH, $jsLayout);
    }
}
