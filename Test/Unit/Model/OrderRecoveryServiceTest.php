<?php

namespace Sezzle\Sezzlepay\Test\Unit\Model;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Payment as QuotePayment;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sezzle\Sezzlepay\Api\V2Interface;
use Sezzle\Sezzlepay\Gateway\Command\AuthorizeCommand;
use Sezzle\Sezzlepay\Helper\Data;
use Sezzle\Sezzlepay\Model\OrderRecoveryService;

/**
 * @covers \Sezzle\Sezzlepay\Model\OrderRecoveryService
 */
class OrderRecoveryServiceTest extends TestCase
{
    /**
     * @var OrderFactory|MockObject
     */
    private $orderFactory;

    /**
     * @var V2Interface|MockObject
     */
    private $v2;

    /**
     * @var Data|MockObject
     */
    private $helper;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var OrderRecoveryService
     */
    private $service;

    public function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->orderFactory = $this->getMockBuilder(OrderFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->v2 = $this->createMock(V2Interface::class);
        $this->helper = $this->createMock(Data::class);

        $this->service = new OrderRecoveryService($this->orderFactory, $this->v2, $this->helper);
    }

    /**
     * @return Quote|MockObject
     */
    private function makeQuote()
    {
        // QuoteStub declares the magic getBase* getters as real methods so they can be mocked
        // under PHPUnit 12 (MockBuilder::addMethods() was removed in PHPUnit 10+).
        return $this->getMockBuilder(QuoteStub::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getId', 'getReservedOrderId', 'getPayment', 'getStoreId',
                'getBaseGrandTotal', 'getBaseCurrencyCode'
            ])
            ->getMock();
    }

    /**
     * @return Order|MockObject
     */
    private function makeOrder()
    {
        return $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['loadByIncrementId', 'getId', 'getQuoteId'])
            ->getMock();
    }

    public function testGetExistingOrderReturnsNullWithoutReservedId()
    {
        $quote = $this->makeQuote();
        $quote->method('getReservedOrderId')->willReturn(null);

        // No reserved id => no DB lookup at all.
        $this->orderFactory->expects($this->never())->method('create');

        $this->assertNull($this->service->getExistingOrder($quote));
    }

    public function testGetExistingOrderReturnsNullWhenOrderNotFound()
    {
        $quote = $this->makeQuote();
        $quote->method('getReservedOrderId')->willReturn('000000123');
        $quote->method('getId')->willReturn(10);

        $order = $this->makeOrder();
        $order->method('loadByIncrementId')->with('000000123')->willReturnSelf();
        $order->method('getId')->willReturn(null);
        $this->orderFactory->method('create')->willReturn($order);

        $this->assertNull($this->service->getExistingOrder($quote));
    }

    public function testGetExistingOrderReturnsNullWhenQuoteMismatch()
    {
        $quote = $this->makeQuote();
        $quote->method('getReservedOrderId')->willReturn('000000123');
        $quote->method('getId')->willReturn(10);

        // The increment id belongs to an unrelated quote's order.
        $order = $this->makeOrder();
        $order->method('loadByIncrementId')->with('000000123')->willReturnSelf();
        $order->method('getId')->willReturn(5);
        $order->method('getQuoteId')->willReturn(99);
        $this->orderFactory->method('create')->willReturn($order);

        $this->assertNull($this->service->getExistingOrder($quote));
    }

    public function testGetExistingOrderReturnsMatchingOrder()
    {
        $quote = $this->makeQuote();
        $quote->method('getReservedOrderId')->willReturn('000000123');
        $quote->method('getId')->willReturn(10);

        $order = $this->makeOrder();
        $order->method('loadByIncrementId')->with('000000123')->willReturnSelf();
        $order->method('getId')->willReturn(5);
        $order->method('getQuoteId')->willReturn(10);
        $this->orderFactory->method('create')->willReturn($order);

        $this->assertSame($order, $this->service->getExistingOrder($quote));
    }

    public function testReleaseDoesNothingWithoutPayment()
    {
        $quote = $this->makeQuote();
        $quote->method('getPayment')->willReturn(null);

        $this->v2->expects($this->never())->method('releasePayment');

        $this->service->releaseStrandedAuthorization($quote);
    }

    public function testReleaseDoesNothingWithoutUuid()
    {
        $quote = $this->makeQuote();
        $payment = $this->createMock(QuotePayment::class);
        $payment->method('getAdditionalInformation')
            ->with(AuthorizeCommand::KEY_ORIGINAL_ORDER_UUID)
            ->willReturn(null);
        $quote->method('getPayment')->willReturn($payment);

        $this->v2->expects($this->never())->method('releasePayment');

        $this->service->releaseStrandedAuthorization($quote);
    }

    public function testReleaseReleasesAuthorizationWhenUuidPresent()
    {
        $quote = $this->makeQuote();
        $payment = $this->createMock(QuotePayment::class);
        $payment->method('getAdditionalInformation')
            ->with(AuthorizeCommand::KEY_ORIGINAL_ORDER_UUID)
            ->willReturn('order-uuid-123');
        $quote->method('getPayment')->willReturn($payment);
        $quote->method('getBaseGrandTotal')->willReturn(100.00);
        $quote->method('getBaseCurrencyCode')->willReturn('USD');
        $quote->method('getStoreId')->willReturn(1);

        $this->v2->expects($this->once())
            ->method('releasePayment')
            ->with('order-uuid-123', 10000, 'USD', 1);
        $this->helper->expects($this->once())->method('logSezzleActions');

        $this->service->releaseStrandedAuthorization($quote);
    }

    public function testReleaseSwallowsExceptionsAndLogs()
    {
        $quote = $this->makeQuote();
        $payment = $this->createMock(QuotePayment::class);
        $payment->method('getAdditionalInformation')
            ->with(AuthorizeCommand::KEY_ORIGINAL_ORDER_UUID)
            ->willReturn('order-uuid-123');
        $quote->method('getPayment')->willReturn($payment);
        $quote->method('getBaseGrandTotal')->willReturn(100.00);
        $quote->method('getBaseCurrencyCode')->willReturn('USD');
        $quote->method('getStoreId')->willReturn(1);

        $this->v2->expects($this->once())
            ->method('releasePayment')
            ->willThrowException(new \Exception('Sezzle API down'));
        // Failure is logged, never rethrown.
        $this->helper->expects($this->once())->method('logSezzleActions');

        $this->service->releaseStrandedAuthorization($quote);
    }
}

/**
 * Test double exposing Magento\Quote\Model\Quote's magic getBase* getters as real methods so they
 * can be mocked under PHPUnit 12, where MockBuilder::addMethods() was removed.
 */
class QuoteStub extends Quote
{
    public function getBaseGrandTotal()
    {
        return null;
    }

    public function getBaseCurrencyCode()
    {
        return null;
    }
}
