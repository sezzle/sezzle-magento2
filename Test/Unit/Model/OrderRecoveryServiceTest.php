<?php

namespace Sezzle\Sezzlepay\Test\Unit\Model;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
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
            ->onlyMethods(['loadByIncrementId', 'loadByIncrementIdAndStoreId', 'getId', 'getQuoteId'])
            ->getMock();
    }

    /**
     * The exception shape Magento actually produces for this failure.
     *
     * submitQuote() replaces whatever threw while it was rolling back a failed submit with a
     * plain \Exception, keeping the *original* failure as its previous and the collision only as
     * text in the message. Nothing in the chain is an AlreadyExistsException.
     *
     * @param \Throwable|null $original
     * @return \Exception
     */
    private function wrappedCollisionException(?\Throwable $original = null): \Exception
    {
        return new \Exception(
            "An exception occurred on 'sales_model_service_quote_submit_failure' event: "
            . 'Unique constraint violation found',
            0,
            $original ?? new LocalizedException(__('Some earlier failure'))
        );
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
        $quote->method('getStoreId')->willReturn(3);

        $order = $this->makeOrder();
        $order->method('loadByIncrementIdAndStoreId')->with('000000123', 3)->willReturnSelf();
        $order->method('getId')->willReturn(null);
        $this->orderFactory->method('create')->willReturn($order);

        $this->assertNull($this->service->getExistingOrder($quote));
    }

    public function testGetExistingOrderReturnsNullWhenQuoteMismatch()
    {
        $quote = $this->makeQuote();
        $quote->method('getReservedOrderId')->willReturn('000000123');
        $quote->method('getId')->willReturn(10);
        $quote->method('getStoreId')->willReturn(3);

        // The increment id belongs to an unrelated quote's order.
        $order = $this->makeOrder();
        $order->method('loadByIncrementIdAndStoreId')->with('000000123', 3)->willReturnSelf();
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
        $quote->method('getStoreId')->willReturn(3);

        $order = $this->makeOrder();
        $order->method('loadByIncrementIdAndStoreId')->with('000000123', 3)->willReturnSelf();
        $order->method('getId')->willReturn(5);
        $order->method('getQuoteId')->willReturn(10);
        $this->orderFactory->method('create')->willReturn($order);

        $this->assertSame($order, $this->service->getExistingOrder($quote));
    }

    public function testGetExistingOrderScopesLookupToTheQuoteStore()
    {
        $quote = $this->makeQuote();
        $quote->method('getReservedOrderId')->willReturn('000000123');
        $quote->method('getId')->willReturn(10);
        $quote->method('getStoreId')->willReturn(3);

        // Increment IDs repeat across stores whenever those stores share a sequence prefix, and
        // sales_order's unique key is (increment_id, store_id). The unscoped loader would return
        // whichever store's row came back first, so it must not be used when a store is known.
        $order = $this->makeOrder();
        $order->expects($this->once())
            ->method('loadByIncrementIdAndStoreId')->with('000000123', 3)->willReturnSelf();
        $order->expects($this->never())->method('loadByIncrementId');
        $order->method('getId')->willReturn(5);
        $order->method('getQuoteId')->willReturn(10);
        $this->orderFactory->method('create')->willReturn($order);

        $this->assertSame($order, $this->service->getExistingOrder($quote));
    }

    public function testGetExistingOrderUsesTheOrderReturnedByTheScopedLoader()
    {
        $quote = $this->makeQuote();
        $quote->method('getReservedOrderId')->willReturn('000000123');
        $quote->method('getId')->willReturn(10);
        $quote->method('getStoreId')->willReturn(3);

        // Unlike loadByIncrementId(), loadByIncrementIdAndStoreId() returns the collection's
        // first item rather than $this - so the return value, not the factory-created instance,
        // holds the loaded order.
        $factoryOrder = $this->makeOrder();
        $loadedOrder = $this->makeOrder();
        $factoryOrder->method('loadByIncrementIdAndStoreId')->willReturn($loadedOrder);
        $factoryOrder->method('getId')->willReturn(null);
        $loadedOrder->method('getId')->willReturn(5);
        $loadedOrder->method('getQuoteId')->willReturn(10);
        $this->orderFactory->method('create')->willReturn($factoryOrder);

        $this->assertSame($loadedOrder, $this->service->getExistingOrder($quote));
    }

    public function testGetExistingOrderFallsBackToUnscopedLookupWithoutStore()
    {
        $quote = $this->makeQuote();
        $quote->method('getReservedOrderId')->willReturn('000000123');
        $quote->method('getId')->willReturn(10);
        $quote->method('getStoreId')->willReturn(null);

        // Filtering on a null store would match nothing; the unscoped lookup is the better
        // approximation for a quote that somehow carries no store.
        $order = $this->makeOrder();
        $order->expects($this->once())->method('loadByIncrementId')->with('000000123')->willReturnSelf();
        $order->expects($this->never())->method('loadByIncrementIdAndStoreId');
        $order->method('getId')->willReturn(5);
        $order->method('getQuoteId')->willReturn(10);
        $this->orderFactory->method('create')->willReturn($order);

        $this->assertSame($order, $this->service->getExistingOrder($quote));
    }

    public function testIsIncrementIdCollisionDetectsMagentosWrappedException()
    {
        // The production case: a bare \Exception whose chain holds no AlreadyExistsException at
        // all. Matching on the exception class alone is what missed this in 7.0.27.
        $this->assertTrue($this->service->isIncrementIdCollision($this->wrappedCollisionException()));
    }

    public function testIsIncrementIdCollisionDetectsTopLevelAlreadyExistsException()
    {
        $this->assertTrue($this->service->isIncrementIdCollision(new AlreadyExistsException()));
    }

    public function testIsIncrementIdCollisionDetectsNestedAlreadyExistsException()
    {
        $wrapped = new \Exception('Something went wrong', 0, new AlreadyExistsException());

        $this->assertTrue($this->service->isIncrementIdCollision($wrapped));
    }

    public function testIsIncrementIdCollisionIgnoresUnrelatedFailure()
    {
        $unrelated = new \Exception(
            'Wrapper',
            0,
            new LocalizedException(__('Please specify a shipping method.'))
        );

        $this->assertFalse($this->service->isIncrementIdCollision($unrelated));
    }

    public function testDescribeThrowableFlattensThePreviousChain()
    {
        $original = new LocalizedException(__('The original failure'));
        $chain = $this->service->describeThrowable($this->wrappedCollisionException($original));

        $this->assertCount(2, $chain);
        $this->assertSame(0, $chain[0]['depth']);
        $this->assertSame(\Exception::class, $chain[0]['class']);
        $this->assertStringContainsString('Unique constraint violation found', $chain[0]['message']);
        // The failure that actually broke order placement is only ever visible here.
        $this->assertSame(1, $chain[1]['depth']);
        $this->assertSame(LocalizedException::class, $chain[1]['class']);
        $this->assertSame('The original failure', $chain[1]['message']);
        $this->assertArrayHasKey('origin', $chain[1]);
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
