<?php

namespace Sezzle\Sezzlepay\Test\Unit\Model;

use Magento\Framework\DB\Adapter\DuplicateException;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Payment as QuotePayment;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteRepository\LoadHandler;
use Magento\Quote\Model\ResourceModel\Quote\Payment as QuotePaymentResource;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
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
     * @var OrderCollectionFactory|MockObject
     */
    private $orderCollectionFactory;

    /**
     * @var OrderCollection|MockObject
     */
    private $orderCollection;

    /**
     * @var QuoteFactory|MockObject
     */
    private $quoteFactory;

    /**
     * @var LoadHandler|MockObject
     */
    private $quoteLoadHandler;

    /**
     * @var QuotePaymentResource|MockObject
     */
    private $quotePaymentResource;

    /**
     * What a load past the cart repository's identity map returns - the quote as committed.
     *
     * @var Quote|MockObject|null
     */
    private $committedQuote;

    /**
     * Order the quote_id lookup currently returns.
     *
     * Held as state and read through a callback rather than restubbed per test: a second
     * willReturn() on an already configured method is ignored, so the setUp default would win.
     *
     * @var Order|MockObject|null
     */
    private $orderForQuoteId;

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
        $this->quoteFactory = $this->getMockBuilder(QuoteFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->committedQuote = null;
        $this->quoteFactory->method('create')->willReturnCallback(function () {
            return $this->committedQuote ?? $this->makeFreshQuote();
        });
        $this->quoteLoadHandler = $this->createMock(LoadHandler::class);
        $this->quotePaymentResource = $this->createMock(QuotePaymentResource::class);

        $this->orderCollection = $this->getMockBuilder(OrderCollection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addFieldToFilter', 'setPageSize', 'getFirstItem'])
            ->getMock();
        $this->orderCollection->method('addFieldToFilter')->willReturnSelf();
        $this->orderCollection->method('setPageSize')->willReturnSelf();
        $this->orderCollection->method('getFirstItem')
            ->willReturnCallback(function () {
                if ($this->orderForQuoteId !== null) {
                    return $this->orderForQuoteId;
                }

                $empty = $this->makeOrder();
                $empty->method('getId')->willReturn(null);

                return $empty;
            });

        $this->orderCollectionFactory = $this->getMockBuilder(OrderCollectionFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->orderCollectionFactory->method('create')->willReturn($this->orderCollection);

        // Default: no order carries this quote_id, so tests exercising the increment-ID
        // fallback reach it. Tests about the quote_id lookup override this.
        $this->orderForQuoteId = null;

        $this->service = new OrderRecoveryService(
            $this->orderFactory,
            $this->v2,
            $this->helper,
            $this->orderCollectionFactory,
            $this->quoteFactory,
            $this->quoteLoadHandler,
            $this->quotePaymentResource
        );
    }

    /**
     * Make the quote_id lookup return the given order.
     *
     * @param Order|MockObject $order
     * @return void
     */
    private function stubOrderForQuoteId($order): void
    {
        $this->orderForQuoteId = $order;
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
     * A quote loaded past the cart repository's identity map: committed state, not the object
     * the rolled-back submitQuote() left behind.
     *
     * @param QuotePayment|MockObject|null $payment the saved payment row; one with an ID if omitted
     * @param int|null $id
     * @param bool $isActive
     * @return Quote|MockObject
     */
    private function makeFreshQuote($payment = null, ?int $id = 42, bool $isActive = true)
    {
        if ($payment === null) {
            $payment = $this->createMock(QuotePayment::class);
            $payment->method('getId')->willReturn(7);
        }
        $fresh = $this->getMockBuilder(QuoteStub::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getIsActive', 'loadByIdWithoutStore', 'getPayment'])
            ->getMock();
        $fresh->method('loadByIdWithoutStore')->willReturnSelf();
        $fresh->method('getId')->willReturn($id);
        $fresh->method('getIsActive')->willReturn($isActive);
        $fresh->method('getPayment')->willReturn($payment);

        return $fresh;
    }

    /**
     * An authorized quote as the failure path holds it, ready to be released.
     *
     * @param QuotePayment|MockObject $payment
     * @return Quote|MockObject
     */
    private function makeReleasableQuote($payment)
    {
        $payment->method('getAdditionalInformation')->willReturnMap([
            [AuthorizeCommand::KEY_ORIGINAL_ORDER_UUID, 'order-uuid-123'],
            [OrderRecoveryService::KEY_AUTH_RELEASED_AT, null]
        ]);
        $quote = $this->makeQuote();
        $quote->method('getId')->willReturn(42);
        $quote->method('getPayment')->willReturn($payment);
        $quote->method('getBaseGrandTotal')->willReturn(100.00);
        $quote->method('getBaseCurrencyCode')->willReturn('USD');
        $quote->method('getStoreId')->willReturn(1);

        return $quote;
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

    public function testGetExistingOrderFindsOrderByQuoteIdWhenIncrementIdWasRewritten()
    {
        $quote = $this->makeQuote();
        $quote->method('getId')->willReturn(10);
        // Ktpl_OrderPrefix rewrites the increment ID after placement, so the reserved ID the
        // quote still holds no longer matches any row. This is the confirmed production shape:
        // before the quote_id lookup, recovery reported no order for a quote that had one, and
        // the caller went on to either place a second order or release a live authorization.
        $quote->method('getReservedOrderId')->willReturn('002411368');
        $quote->method('getStoreId')->willReturn(3);

        $placed = $this->makeOrder();
        $placed->method('getId')->willReturn(7);
        $this->stubOrderForQuoteId($placed);

        // The increment-ID lookup must not even be attempted once quote_id has answered.
        $this->orderFactory->expects($this->never())->method('create');

        $this->assertSame($placed, $this->service->getExistingOrder($quote));
    }

    public function testGetExistingOrderStillFallsBackToIncrementIdWithoutAQuoteMatch()
    {
        $quote = $this->makeQuote();
        $quote->method('getId')->willReturn(10);
        $quote->method('getReservedOrderId')->willReturn('000000123');
        $quote->method('getStoreId')->willReturn(3);

        $order = $this->makeOrder();
        $order->expects($this->once())
            ->method('loadByIncrementIdAndStoreId')->with('000000123', 3)->willReturnSelf();
        $order->method('getId')->willReturn(5);
        $order->method('getQuoteId')->willReturn(10);
        $this->orderFactory->method('create')->willReturn($order);

        $this->assertSame($order, $this->service->getExistingOrder($quote));
    }

    public function testIsIncrementIdCollisionDetectsDuplicateExceptionWithoutTheEnglishPhrase()
    {
        // The collision phrase goes through __(), so it is absent on a translated locale. The
        // driver signal is not translated, which is why it is matched as well.
        $translated = new \Exception(
            "An exception occurred on 'sales_model_service_quote_submit_failure' event: "
            . 'Verletzung der Eindeutigkeitsbedingung gefunden',
            0,
            new DuplicateException('SQLSTATE[23000]: Integrity constraint violation: 1062')
        );

        $this->assertTrue($this->service->isIncrementIdCollision($translated));
    }

    public function testIsIncrementIdCollisionDetectsASqlStateCodeAnywhereInTheChain()
    {
        $driver = new \Exception('SQLSTATE[23000]: Integrity constraint violation', '23000');

        $this->assertTrue($this->service->isIncrementIdCollision(new \Exception('Wrapper', 0, $driver)));
    }

    public function testIsIncrementIdCollisionNarrowedToThisCheckoutMatchesTheReservedId()
    {
        // The generic phrase and the "Duplicate entry '...'" text that names the value are
        // raised at different levels, so the reserved ID is looked for across the whole chain
        // rather than in the link that carried the conflict.
        $failure = $this->wrappedCollisionException(
            new \Exception("SQLSTATE[23000]: Duplicate entry 'LS-002411368-1' for key 'UNQ_...'")
        );

        $this->assertTrue($this->service->isIncrementIdCollision($failure, '002411368'));
    }

    public function testIsIncrementIdCollisionNarrowedToThisCheckoutRejectsAnUnrelatedConflict()
    {
        // A duplicate coupon-usage row is a unique conflict too. Regenerating the reserved ID
        // and resubmitting an already-authorized payment cannot fix it, so the narrowed form
        // sends it to the caller's recover-or-release path instead of the retry.
        $unrelated = new \Exception(
            'Wrapper',
            0,
            new AlreadyExistsException(__("Duplicate entry '42-7' for key 'UNQ_SALESRULE_COUPON_USAGE'"))
        );

        $this->assertFalse($this->service->isIncrementIdCollision($unrelated, '000000123'));
        // Unnarrowed, the same failure still reports a conflict - the diagnostic flag would
        // rather over-report than miss one.
        $this->assertTrue($this->service->isIncrementIdCollision($unrelated));
    }

    public function testDescribeThrowableTruncatesLongDriverMessages()
    {
        // Driver exceptions embed the failing SQL with its bound values, which on a checkout
        // failure is shopper PII, in a file merchants email to support.
        $long = 'SQLSTATE[23000]: ' . str_repeat('a', 900) . 'shopper@example.com';
        $chain = $this->service->describeThrowable(new \Exception($long));

        $this->assertLessThan(mb_strlen($long), mb_strlen($chain[0]['message']));
        $this->assertStringEndsWith('... [truncated]', $chain[0]['message']);
        $this->assertStringNotContainsString('shopper@example.com', $chain[0]['message']);
    }

    public function testReleaseDoesNotReleaseTwiceForTheSameQuote()
    {
        $quote = $this->makeQuote();
        $payment = $this->createMock(QuotePayment::class);
        // A shopper who reloads the return URL runs the whole flow again. Without this guard the
        // second pass sends another releasePayment() for the same UUID, and if placement
        // succeeds that time Magento ends up holding an order whose authorization was released.
        $payment->method('getAdditionalInformation')->willReturnMap([
            [AuthorizeCommand::KEY_ORIGINAL_ORDER_UUID, 'order-uuid-123'],
            [OrderRecoveryService::KEY_AUTH_RELEASED_AT, 1758200000]
        ]);
        $quote->method('getPayment')->willReturn($payment);

        $this->v2->expects($this->never())->method('releasePayment');
        $this->quotePaymentResource->expects($this->never())->method('save');
        $this->helper->expects($this->once())->method('logSezzleActions');

        $this->service->releaseStrandedAuthorization($quote);
    }

    public function testReleaseIsStillReportedWhenTheStampCannotBeSaved()
    {
        $quote = $this->makeQuote();
        $payment = $this->createMock(QuotePayment::class);
        $payment->method('getAdditionalInformation')->willReturnMap([
            [AuthorizeCommand::KEY_ORIGINAL_ORDER_UUID, 'order-uuid-123'],
            [OrderRecoveryService::KEY_AUTH_RELEASED_AT, null]
        ]);
        $quote->method('getPayment')->willReturn($payment);
        $quote->method('getBaseGrandTotal')->willReturn(100.00);
        $quote->method('getBaseCurrencyCode')->willReturn('USD');
        $quote->method('getStoreId')->willReturn(1);

        $this->v2->expects($this->once())->method('releasePayment');
        // The release has already happened by then; failing to record it must not report the
        // release itself as failed.
        $this->quotePaymentResource->method('save')->willThrowException(new \Exception('Payment save failed'));
        $this->helper->expects($this->exactly(2))->method('logSezzleActions');

        $this->service->releaseStrandedAuthorization($quote);
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
        $payment->method('getAdditionalInformation')->willReturnMap([
            [AuthorizeCommand::KEY_ORIGINAL_ORDER_UUID, null]
        ]);
        $quote->method('getPayment')->willReturn($payment);

        $this->v2->expects($this->never())->method('releasePayment');

        $this->service->releaseStrandedAuthorization($quote);
    }

    public function testReleaseReleasesAuthorizationWhenUuidPresent()
    {
        $quote = $this->makeQuote();
        $payment = $this->createMock(QuotePayment::class);
        $payment->method('getAdditionalInformation')->willReturnMap([
            [AuthorizeCommand::KEY_ORIGINAL_ORDER_UUID, 'order-uuid-123'],
            [OrderRecoveryService::KEY_AUTH_RELEASED_AT, null]
        ]);
        $quote->method('getId')->willReturn(42);
        $quote->method('getPayment')->willReturn($payment);
        $quote->method('getBaseGrandTotal')->willReturn(100.00);
        $quote->method('getBaseCurrencyCode')->willReturn('USD');
        $quote->method('getStoreId')->willReturn(1);

        // The quote in hand came out of a rolled-back submitQuote(), so the stamp is written
        // against committed state instead - see testStampNeverSavesTheQuoteItWasHandedOn.
        $freshPayment = $this->createMock(QuotePayment::class);
        $freshPayment->method('getId')->willReturn(7);
        $this->committedQuote = $this->makeFreshQuote($freshPayment);

        $this->v2->expects($this->once())
            ->method('releasePayment')
            ->with('order-uuid-123', 10000, 'USD', 1);
        // Released authorizations are stamped on the payment so a repeat visit can tell that
        // this one has already been given back.
        $freshPayment->expects($this->once())
            ->method('setAdditionalInformation')
            ->with(OrderRecoveryService::KEY_AUTH_RELEASED_AT, $this->isInt());
        $this->quotePaymentResource->expects($this->once())->method('save')->with($freshPayment);
        $this->helper->expects($this->once())->method('logSezzleActions');

        $this->service->releaseStrandedAuthorization($quote);
    }

    public function testStampWritesOnlyTheCommittedPaymentRow()
    {
        // The object on this path is the one the rolled-back submitQuote() mutated, and the cart
        // repository's get() would hand that same object back. The stamp is written to the
        // payment row of a quote loaded past the repository, and nothing else is saved.
        $payment = $this->createMock(QuotePayment::class);
        $quote = $this->makeReleasableQuote($payment);
        $freshPayment = $this->createMock(QuotePayment::class);
        $freshPayment->method('getId')->willReturn(7);
        $this->committedQuote = $this->makeFreshQuote($freshPayment);

        $payment->expects($this->never())->method('setAdditionalInformation');
        $freshPayment->expects($this->once())
            ->method('setAdditionalInformation')
            ->with(OrderRecoveryService::KEY_AUTH_RELEASED_AT, $this->isInt());
        $this->quotePaymentResource->expects($this->once())
            ->method('save')
            ->willReturnCallback(function ($saved) use ($payment, $freshPayment) {
                $this->assertNotSame($payment, $saved);
                $this->assertSame($freshPayment, $saved);
            });

        $this->service->releaseStrandedAuthorization($quote);
    }

    public function testStampDoesNotInventAPaymentRowTheQuoteNeverHad()
    {
        // Quote::getPayment() creates an unsaved payment when none is stored. Writing that would
        // add a second payment row to the quote rather than stamp the existing one.
        $quote = $this->makeReleasableQuote($this->createMock(QuotePayment::class));
        $unsaved = $this->createMock(QuotePayment::class);
        $unsaved->method('getId')->willReturn(null);
        $this->committedQuote = $this->makeFreshQuote($unsaved);

        $this->v2->expects($this->once())->method('releasePayment');
        $this->quotePaymentResource->expects($this->never())->method('save');
        // The release is still reported, alongside the failure to record it.
        $this->helper->expects($this->exactly(2))->method('logSezzleActions');

        $this->service->releaseStrandedAuthorization($quote);
    }

    public function testLoadCommittedQuoteLoadsPastTheRepositoryAndRunsTheLoadHandler()
    {
        $fresh = $this->makeFreshQuote();
        $fresh->expects($this->once())->method('loadByIdWithoutStore')->with(42);
        $this->committedQuote = $fresh;

        // Without the load handler the quote carries no items or shipping assignments, and
        // CartRepository::save() would fill both in from its stale cached copy.
        $this->quoteLoadHandler->expects($this->once())->method('load')->with($fresh);

        $this->assertSame($fresh, $this->service->loadCommittedQuote(42));
    }

    public function testLoadCommittedQuoteRefusesAMissingQuote()
    {
        $this->committedQuote = $this->makeFreshQuote(null, null);
        $this->quoteLoadHandler->expects($this->never())->method('load');

        $this->expectException(NoSuchEntityException::class);
        $this->service->loadCommittedQuote(42);
    }

    public function testLoadCommittedQuoteRefusesAnInactiveQuote()
    {
        // The load handler skips inactive quotes, so one would reach a save without its own items.
        $this->committedQuote = $this->makeFreshQuote(null, 42, false);
        $this->quoteLoadHandler->expects($this->never())->method('load');

        $this->expectException(NoSuchEntityException::class);
        $this->service->loadCommittedQuote(42);
    }

    public function testReleaseSwallowsExceptionsAndLogs()
    {
        $quote = $this->makeQuote();
        $payment = $this->createMock(QuotePayment::class);
        $payment->method('getAdditionalInformation')->willReturnMap([
            [AuthorizeCommand::KEY_ORIGINAL_ORDER_UUID, 'order-uuid-123'],
            [OrderRecoveryService::KEY_AUTH_RELEASED_AT, null]
        ]);
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
