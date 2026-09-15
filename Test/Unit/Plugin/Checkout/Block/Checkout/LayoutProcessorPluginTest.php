<?php

namespace Sezzle\Sezzlepay\Test\Unit\Plugin\Checkout\Block\Checkout;

use Magento\Checkout\Block\Checkout\LayoutProcessor;
use Magento\Checkout\Model\Session as CheckoutSession;
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
        $this->config = $this->createMock(Config::class);
        $this->checkoutSession = $this->getMockBuilder(CheckoutSession::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getQuote'])
            ->getMock();
        $this->subject = $this->createMock(LayoutProcessor::class);

        $this->plugin = new LayoutProcessorPlugin(
            $this->config,
            $this->checkoutSession,
            $this->createMock(Data::class)
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
        $methods = ['sezzlepay' => ['isBillingAddressRequired' => true]];
        $renders = ['children' => ['sezzlepay' => ['methods' => $methods]]];
        $payment = ['children' => ['renders' => $renders]];
        $steps = ['children' => ['billing-step' => ['children' => ['payment' => $payment]]]];

        return ['components' => ['checkout' => ['children' => ['steps' => $steps]]]];
    }

    /**
     * @param array $jsLayout
     * @return bool
     */
    private function readFlag(array $jsLayout): bool
    {
        return $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']
            ['children']['payment']['children']['renders']['children']['sezzlepay']['methods']
            ['sezzlepay']['isBillingAddressRequired'];
    }
}
