<?php

namespace Sezzle\Sezzlepay\Test\Unit\Controller\Payment;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Payment as QuotePayment;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sezzle\Sezzlepay\Api\CartManagementInterface;
use Sezzle\Sezzlepay\Api\GuestCartManagementInterface;
use Sezzle\Sezzlepay\Api\V2Interface;
use Sezzle\Sezzlepay\Controller\Payment\Complete;
use Sezzle\Sezzlepay\Gateway\Command\AuthorizeCommand;
use Sezzle\Sezzlepay\Helper\Data;
use Sezzle\Sezzlepay\Model\OrderRecoveryService;
use Sezzle\Sezzlepay\Model\Tokenize;

/**
 * @covers \Sezzle\Sezzlepay\Controller\Payment\Complete
 */
class CompleteTest extends TestCase
{
    private const SUCCESS_PATH = 'checkout/onepage/success';
    private const CART_PATH = 'checkout/cart';

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var CustomerSession|MockObject
     */
    private $customerSession;

    /**
     * @var CheckoutSession|MockObject
     */
    private $checkoutSession;

    /**
     * @var OrderFactory|MockObject
     */
    private $orderFactory;

    /**
     * @var Data|MockObject
     */
    private $helper;

    /**
     * @var Tokenize|MockObject
     */
    private $tokenize;

    /**
     * @var ManagerInterface|MockObject
     */
    private $messageManager;

    /**
     * @var RedirectFactory|MockObject
     */
    private $resultRedirectFactory;

    /**
     * @var Redirect|MockObject
     */
    private $redirect;

    /**
     * @var QuoteIdToMaskedQuoteIdInterface|MockObject
     */
    private $quoteIdToMaskedQuoteId;

    /**
     * @var CartManagementInterface|MockObject
     */
    private $cartManagement;

    /**
     * @var GuestCartManagementInterface|MockObject
     */
    private $guestCartManagement;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $cartRepository;

    /**
     * @var V2Interface|MockObject
     */
    private $v2;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var Complete
     */
    private $controller;

    public function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->request = $this->createMock(RequestInterface::class);
        $this->customerSession = $this->createMock(CustomerSession::class);
        // CheckoutSessionStub declares the magic Last* setters as real methods so they can
        // be mocked under PHPUnit 12 (MockBuilder::addMethods() was removed in PHPUnit 10+).
        $this->checkoutSession = $this->getMockBuilder(CheckoutSessionStub::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getQuote', 'setLastQuoteId', 'setLastSuccessQuoteId', 'setLastOrderId',
                'setLastRealOrderId', 'setLastOrderStatus'
            ])
            ->getMock();
        // The Last* setters are chained in restoreCheckoutSession(); each returns the session.
        $this->checkoutSession->method('setLastQuoteId')->willReturnSelf();
        $this->checkoutSession->method('setLastSuccessQuoteId')->willReturnSelf();
        $this->checkoutSession->method('setLastOrderId')->willReturnSelf();
        $this->checkoutSession->method('setLastRealOrderId')->willReturnSelf();
        $this->checkoutSession->method('setLastOrderStatus')->willReturnSelf();
        $this->orderFactory = $this->getMockBuilder(OrderFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->helper = $this->createMock(Data::class);
        $this->tokenize = $this->createMock(Tokenize::class);
        $this->messageManager = $this->createMock(ManagerInterface::class);
        $this->redirect = $this->createMock(Redirect::class);
        $this->redirect->method('setPath')->willReturnSelf();
        $this->resultRedirectFactory = $this->getMockBuilder(RedirectFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->resultRedirectFactory->method('create')->willReturn($this->redirect);
        $this->quoteIdToMaskedQuoteId = $this->createMock(QuoteIdToMaskedQuoteIdInterface::class);
        $this->cartManagement = $this->createMock(CartManagementInterface::class);
        $this->guestCartManagement = $this->createMock(GuestCartManagementInterface::class);
        $this->cartRepository = $this->createMock(CartRepositoryInterface::class);
        $this->v2 = $this->createMock(V2Interface::class);

        // The controller delegates order lookup / authorization release to OrderRecoveryService.
        // Use a real service over the same orderFactory/v2/helper mocks so the existing
        // expectations on those collaborators continue to exercise the delegated behaviour.
        $orderRecovery = $this->makeOrderRecovery();

        $this->controller = $this->objectManager->getObject(
            Complete::class,
            [
                'request' => $this->request,
                'customerSession' => $this->customerSession,
                'checkoutSession' => $this->checkoutSession,
                'orderFactory' => $this->orderFactory,
                'helper' => $this->helper,
                'tokenize' => $this->tokenize,
                'messageManager' => $this->messageManager,
                'resultRedirectFactory' => $this->resultRedirectFactory,
                'quoteIdToMaskedQuoteIdInterface' => $this->quoteIdToMaskedQuoteId,
                'cartManagement' => $this->cartManagement,
                'guestCartManagement' => $this->guestCartManagement,
                'cartRepository' => $this->cartRepository,
                'orderRecovery' => $orderRecovery,
            ]
        );
    }

    /**
     * Build the real recovery service the entry point delegates to.
     *
     * The quote_id lookup is stubbed to find nothing by default, so these tests continue to
     * exercise the increment-ID path they were written for; recovery by quote_id has its own
     * coverage in OrderRecoveryServiceTest.
     *
     * @return OrderRecoveryService
     */
    private function makeOrderRecovery(): OrderRecoveryService
    {
        $emptyOrder = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->getMock();
        $emptyOrder->method('getId')->willReturn(null);

        $collection = $this->getMockBuilder(OrderCollection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addFieldToFilter', 'setPageSize', 'getFirstItem'])
            ->getMock();
        $collection->method('addFieldToFilter')->willReturnSelf();
        $collection->method('setPageSize')->willReturnSelf();
        $collection->method('getFirstItem')->willReturn($emptyOrder);

        $collectionFactory = $this->getMockBuilder(OrderCollectionFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $collectionFactory->method('create')->willReturn($collection);

        return new OrderRecoveryService(
            $this->orderFactory,
            $this->v2,
            $this->helper,
            $collectionFactory,
            $this->cartRepository
        );
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
                'getId', 'getReservedOrderId', 'setReservedOrderId',
                'reserveOrderId', 'getPayment', 'getStoreId',
                'getBaseGrandTotal', 'getBaseCurrencyCode'
            ])
            ->getMock();
    }

    /**
     * A quote as the repository hands it back: committed state, not the object the rolled-back
     * submitQuote() left behind.
     *
     * @return Quote|MockObject
     */
    private function makeFreshQuote()
    {
        $fresh = $this->getMockBuilder(QuoteStub::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getId', 'getReservedOrderId', 'setReservedOrderId',
                'reserveOrderId', 'getPayment'
            ])
            ->getMock();
        $fresh->method('getPayment')->willReturn($this->createMock(QuotePayment::class));

        return $fresh;
    }

    /**
     * @return Order|MockObject
     */
    private function makeOrder()
    {
        return $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'loadByIncrementId', 'loadByIncrementIdAndStoreId',
                'getId', 'getQuoteId', 'getIncrementId', 'getStatus'
            ])
            ->getMock();
    }

    /**
     * The exception shape Magento actually produces for this failure.
     *
     * submitQuote() replaces whatever threw while it was rolling back a failed submit with a
     * plain \Exception, keeping the original failure as its previous and the collision only as
     * text. This is neither an AlreadyExistsException nor a LocalizedException, which is why
     * 7.0.27's recovery never ran and the shopper reached a Magento error report page instead.
     *
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

    public function testSuccessfulPlacementRedirectsToSuccess()
    {
        $quote = $this->makeQuote();
        $this->checkoutSession->method('getQuote')->willReturn($quote);
        // No reserved id yet -> no pre-existing order.
        $quote->method('getReservedOrderId')->willReturn(null);
        $this->request->method('getParam')->with('customer-uuid')->willReturn(null);
        $this->customerSession->method('isLoggedIn')->willReturn(true);
        $quote->method('getId')->willReturn(10);

        $this->cartManagement->expects($this->once())
            ->method('placeOrder')->with(10)->willReturn(100);
        $this->guestCartManagement->expects($this->never())->method('placeOrder');

        $this->redirect->expects($this->once())->method('setPath')->with(self::SUCCESS_PATH);

        $this->assertSame($this->redirect, $this->controller->execute());
    }

    public function testAlreadyPlacedOrderSkipsResubmission()
    {
        $quote = $this->makeQuote();
        $this->checkoutSession->method('getQuote')->willReturn($quote);
        $quote->method('getReservedOrderId')->willReturn('000000123');
        $quote->method('getId')->willReturn(10);
        $quote->method('getStoreId')->willReturn(3);

        $order = $this->makeOrder();
        $order->method('loadByIncrementIdAndStoreId')->with('000000123', 3)->willReturnSelf();
        $order->method('getId')->willReturn(5);
        $order->method('getQuoteId')->willReturn(10);
        $order->method('getIncrementId')->willReturn('000000123');
        $order->method('getStatus')->willReturn('pending');
        $this->orderFactory->method('create')->willReturn($order);

        // The order already exists -> must NOT resubmit and must NOT release.
        $this->cartManagement->expects($this->never())->method('placeOrder');
        $this->guestCartManagement->expects($this->never())->method('placeOrder');
        $this->v2->expects($this->never())->method('releasePayment');

        // Checkout session is restored for the success page.
        $this->checkoutSession->expects($this->once())
            ->method('setLastRealOrderId')->with('000000123')->willReturnSelf();

        $this->redirect->expects($this->once())->method('setPath')->with(self::SUCCESS_PATH);

        $this->assertSame($this->redirect, $this->controller->execute());
    }

    public function testUnrecoverableFailureReleasesAuthorizationAndRedirectsToCart()
    {
        $quote = $this->makeQuote();
        $this->checkoutSession->method('getQuote')->willReturn($quote);
        $quote->method('getReservedOrderId')->willReturn(null);
        $this->request->method('getParam')->with('customer-uuid')->willReturn(null);
        $this->customerSession->method('isLoggedIn')->willReturn(true);
        $quote->method('getId')->willReturn(10);

        $this->cartManagement->expects($this->once())
            ->method('placeOrder')->with(10)
            ->willThrowException(new LocalizedException(__('Some failure')));

        // The stranded Sezzle authorization must be released.
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
            ->with('order-uuid-123', 10000, 'USD', 1);
        // The stamp is written against committed state, not against the object the rolled-back
        // submitQuote() left behind.
        $this->cartRepository->method('get')->with(10)->willReturn($this->makeFreshQuote());

        $this->messageManager->expects($this->once())->method('addErrorMessage');

        $this->redirect->expects($this->once())->method('setPath')->with(self::CART_PATH);

        $this->assertSame($this->redirect, $this->controller->execute());
    }

    public function testReservedIdCollisionReturnsConcurrentlyPlacedOrder()
    {
        $quote = $this->makeQuote();
        $this->checkoutSession->method('getQuote')->willReturn($quote);
        $quote->method('getReservedOrderId')->willReturn('000000123');
        $quote->method('getId')->willReturn(10);
        $quote->method('getStoreId')->willReturn(3);
        $this->request->method('getParam')->with('customer-uuid')->willReturn(null);
        $this->customerSession->method('isLoggedIn')->willReturn(true);

        // First lookup (idempotency guard) finds nothing; placeOrder then collides because a
        // concurrent request placed the order; the second lookup finds it.
        $emptyOrder = $this->makeOrder();
        $emptyOrder->method('loadByIncrementIdAndStoreId')->willReturnSelf();
        $emptyOrder->method('getId')->willReturn(null);

        $foundOrder = $this->makeOrder();
        $foundOrder->method('loadByIncrementIdAndStoreId')->with('000000123', 3)->willReturnSelf();
        $foundOrder->method('getId')->willReturn(5);
        $foundOrder->method('getQuoteId')->willReturn(10);
        $foundOrder->method('getIncrementId')->willReturn('000000123');
        $foundOrder->method('getStatus')->willReturn('pending');
        $this->orderFactory->method('create')->willReturnOnConsecutiveCalls($emptyOrder, $foundOrder);

        $this->cartManagement->expects($this->once())
            ->method('placeOrder')->with(10)
            ->willThrowException(new AlreadyExistsException(__('Unique constraint violation found')));
        $this->guestCartManagement->expects($this->never())->method('placeOrder');

        // The order exists, so no fresh id is reserved and no authorization is released.
        $quote->expects($this->never())->method('reserveOrderId');
        $this->cartRepository->expects($this->never())->method('save');
        $this->v2->expects($this->never())->method('releasePayment');

        $this->checkoutSession->expects($this->once())
            ->method('setLastRealOrderId')->with('000000123')->willReturnSelf();
        $this->redirect->expects($this->once())->method('setPath')->with(self::SUCCESS_PATH);

        $this->assertSame($this->redirect, $this->controller->execute());
    }

    public function testReservedIdCollisionRegeneratesIdAndRetries()
    {
        $quote = $this->makeQuote();
        $this->checkoutSession->method('getQuote')->willReturn($quote);
        $quote->method('getReservedOrderId')->willReturn('000000123');
        $quote->method('getId')->willReturn(10);
        $quote->method('getStoreId')->willReturn(3);
        $this->request->method('getParam')->with('customer-uuid')->willReturn(null);
        $this->customerSession->method('isLoggedIn')->willReturn(true);

        // Both lookups find no order for this quote: the increment id was consumed by an
        // unrelated order, so a fresh id must be reserved and placeOrder retried once.
        $emptyOrder = $this->makeOrder();
        $emptyOrder->method('loadByIncrementIdAndStoreId')->willReturnSelf();
        $emptyOrder->method('getId')->willReturn(null);
        $this->orderFactory->method('create')->willReturn($emptyOrder);

        $calls = 0;
        $this->cartManagement->expects($this->exactly(2))
            ->method('placeOrder')->with(10)
            ->willReturnCallback(function () use (&$calls) {
                if (++$calls === 1) {
                    // The shape MySQL actually produces when the reserved increment ID has
                    // been consumed: the duplicate-entry text names the value, which is what
                    // ties the conflict to this checkout rather than to some other unique key.
                    throw new AlreadyExistsException(
                        __("Unique constraint violation found"),
                        new \Exception("SQLSTATE[23000]: Duplicate entry '000000123-3' for key 'UNQ_SALES_ORDER'")
                    );
                }
                return 200;
            });

        // The quote is re-read from committed state before the retry, because the copy in hand
        // carries mutations from the submitQuote() that rolled back.
        $this->cartRepository->expects($this->once())->method('get')->with(10)->willReturn($quote);

        // A fresh reserved id is generated and persisted before the retry.
        $quote->expects($this->once())->method('setReservedOrderId')->with(null);
        $quote->expects($this->once())->method('reserveOrderId')->willReturnSelf();
        $this->cartRepository->expects($this->once())->method('save')->with($quote);
        $this->v2->expects($this->never())->method('releasePayment');

        $this->redirect->expects($this->once())->method('setPath')->with(self::SUCCESS_PATH);

        $this->assertSame($this->redirect, $this->controller->execute());
    }

    public function testWrappedCollisionExceptionRecoversConcurrentlyPlacedOrder()
    {
        $quote = $this->makeQuote();
        $this->checkoutSession->method('getQuote')->willReturn($quote);
        $quote->method('getReservedOrderId')->willReturn('000000123');
        $quote->method('getId')->willReturn(10);
        $quote->method('getStoreId')->willReturn(3);
        $this->request->method('getParam')->with('customer-uuid')->willReturn(null);
        $this->customerSession->method('isLoggedIn')->willReturn(true);

        $emptyOrder = $this->makeOrder();
        $emptyOrder->method('loadByIncrementIdAndStoreId')->willReturnSelf();
        $emptyOrder->method('getId')->willReturn(null);

        $foundOrder = $this->makeOrder();
        $foundOrder->method('loadByIncrementIdAndStoreId')->with('000000123', 3)->willReturnSelf();
        $foundOrder->method('getId')->willReturn(5);
        $foundOrder->method('getQuoteId')->willReturn(10);
        $foundOrder->method('getIncrementId')->willReturn('000000123');
        $foundOrder->method('getStatus')->willReturn('pending');
        $this->orderFactory->method('create')->willReturnOnConsecutiveCalls($emptyOrder, $foundOrder);

        // Magento's wrapper, not an AlreadyExistsException: the collision has to be recognised
        // through the message rather than the class.
        $this->cartManagement->expects($this->once())
            ->method('placeOrder')->with(10)
            ->willThrowException($this->wrappedCollisionException());

        $quote->expects($this->never())->method('reserveOrderId');
        $this->v2->expects($this->never())->method('releasePayment');
        $this->messageManager->expects($this->never())->method('addErrorMessage');

        $this->checkoutSession->expects($this->once())
            ->method('setLastRealOrderId')->with('000000123')->willReturnSelf();
        $this->redirect->expects($this->once())->method('setPath')->with(self::SUCCESS_PATH);

        $this->assertSame($this->redirect, $this->controller->execute());
    }

    public function testWrappedCollisionExceptionRegeneratesIdAndRetriesWhenItNamesTheReservedId()
    {
        $quote = $this->makeQuote();
        $this->checkoutSession->method('getQuote')->willReturn($quote);
        $quote->method('getReservedOrderId')->willReturn('000000123');
        $quote->method('getId')->willReturn(10);
        $quote->method('getStoreId')->willReturn(3);
        $this->request->method('getParam')->with('customer-uuid')->willReturn(null);
        $this->customerSession->method('isLoggedIn')->willReturn(true);

        $emptyOrder = $this->makeOrder();
        $emptyOrder->method('loadByIncrementIdAndStoreId')->willReturnSelf();
        $emptyOrder->method('getId')->willReturn(null);
        $this->orderFactory->method('create')->willReturn($emptyOrder);

        $calls = 0;
        $this->cartManagement->expects($this->exactly(2))
            ->method('placeOrder')->with(10)
            ->willReturnCallback(function () use (&$calls) {
                if (++$calls === 1) {
                    // Magento's wrapper carries the generic phrase; the driver exception it
                    // wraps is where the increment ID itself survives. The two are matched
                    // across the chain rather than within one link for exactly this reason.
                    throw $this->wrappedCollisionException(
                        new \Exception("SQLSTATE[23000]: Duplicate entry '000000123-3' for key 'UNQ_SALES_ORDER'")
                    );
                }
                return 200;
            });

        $this->cartRepository->expects($this->once())->method('get')->with(10)->willReturn($quote);
        $quote->expects($this->once())->method('setReservedOrderId')->with(null);
        $quote->expects($this->once())->method('reserveOrderId')->willReturnSelf();
        $this->cartRepository->expects($this->once())->method('save')->with($quote);
        $this->v2->expects($this->never())->method('releasePayment');

        $this->redirect->expects($this->once())->method('setPath')->with(self::SUCCESS_PATH);

        $this->assertSame($this->redirect, $this->controller->execute());
    }

    /**
     * How the two writes on this path interact. The retry regenerates the reserved ID and
     * saves it; the retry then fails for its own reasons, so the release path saves too. If
     * that second write went through the quote in hand - the object the rolled-back
     * submitQuote() mutated, which execute() still holds - it would put the abandoned reserved
     * ID back over the one just persisted.
     */
    public function testReleaseAfterAFailedRetryDoesNotOverwriteTheRegeneratedReservedId()
    {
        $quote = $this->makeQuote();
        $this->checkoutSession->method('getQuote')->willReturn($quote);
        $quote->method('getReservedOrderId')->willReturn('000000123');
        $quote->method('getId')->willReturn(10);
        $quote->method('getStoreId')->willReturn(3);
        $this->request->method('getParam')->with('customer-uuid')->willReturn(null);
        $this->customerSession->method('isLoggedIn')->willReturn(true);

        $emptyOrder = $this->makeOrder();
        $emptyOrder->method('loadByIncrementIdAndStoreId')->willReturnSelf();
        $emptyOrder->method('getId')->willReturn(null);
        $this->orderFactory->method('create')->willReturn($emptyOrder);

        $calls = 0;
        $this->cartManagement->expects($this->exactly(2))
            ->method('placeOrder')->with(10)
            ->willReturnCallback(function () use (&$calls) {
                if (++$calls === 1) {
                    throw new AlreadyExistsException(
                        __("Unique constraint violation found"),
                        new \Exception("SQLSTATE[23000]: Duplicate entry '000000123-3' for key 'UNQ_SALES_ORDER'")
                    );
                }
                // The retry fails on its own, so the authorization still has to be released.
                throw new LocalizedException(__('Some failure'));
            });

        $fresh = $this->makeFreshQuote();
        $fresh->method('getReservedOrderId')->willReturn('000000124');
        $this->cartRepository->method('get')->with(10)->willReturn($fresh);

        $payment = $this->createMock(QuotePayment::class);
        $payment->method('getAdditionalInformation')->willReturnMap([
            [AuthorizeCommand::KEY_ORIGINAL_ORDER_UUID, 'order-uuid-123'],
            [OrderRecoveryService::KEY_AUTH_RELEASED_AT, null]
        ]);
        $quote->method('getPayment')->willReturn($payment);
        $quote->method('getBaseGrandTotal')->willReturn(100.00);
        $quote->method('getBaseCurrencyCode')->willReturn('USD');

        $this->v2->expects($this->once())->method('releasePayment');

        // Neither write touches the stale object: the retry reassigns its own local, and the
        // stamp re-reads for itself.
        $quote->expects($this->never())->method('setReservedOrderId');
        $payment->expects($this->never())->method('setAdditionalInformation');

        $saved = [];
        $this->cartRepository->expects($this->exactly(2))
            ->method('save')
            ->willReturnCallback(function ($cart) use (&$saved) {
                $saved[] = $cart;
            });

        $this->redirect->expects($this->once())->method('setPath')->with(self::CART_PATH);

        $this->assertSame($this->redirect, $this->controller->execute());
        $this->assertSame([$fresh, $fresh], $saved);
    }

    public function testUnattributableCollisionReleasesRatherThanResubmitting()
    {
        $quote = $this->makeQuote();
        $this->checkoutSession->method('getQuote')->willReturn($quote);
        $quote->method('getReservedOrderId')->willReturn('000000123');
        $quote->method('getId')->willReturn(10);
        $quote->method('getStoreId')->willReturn(3);
        $this->request->method('getParam')->with('customer-uuid')->willReturn(null);
        $this->customerSession->method('isLoggedIn')->willReturn(true);

        $emptyOrder = $this->makeOrder();
        $emptyOrder->method('loadByIncrementIdAndStoreId')->willReturnSelf();
        $emptyOrder->method('getId')->willReturn(null);
        $this->orderFactory->method('create')->willReturn($emptyOrder);

        // Nothing in this chain ties the conflict to this checkout's reserved ID - the shape
        // Magento produces when the collision survives only as the generic phrase. Some unique
        // key collided, but not demonstrably this one, and resubmitting an already-authorized
        // payment on that guess is the more expensive mistake. Release instead.
        $this->cartManagement->expects($this->once())
            ->method('placeOrder')->with(10)
            ->willThrowException($this->wrappedCollisionException());

        // No regenerate-and-retry: the reserved ID is left alone and placeOrder is not called
        // a second time. (The re-read quote is still saved once, further down, to stamp the
        // release.)
        $quote->expects($this->never())->method('reserveOrderId');
        $quote->expects($this->never())->method('setReservedOrderId');

        $payment = $this->createMock(QuotePayment::class);
        $payment->method('getAdditionalInformation')->willReturnMap([
            [AuthorizeCommand::KEY_ORIGINAL_ORDER_UUID, 'order-uuid-123'],
            [OrderRecoveryService::KEY_AUTH_RELEASED_AT, null]
        ]);
        $quote->method('getPayment')->willReturn($payment);
        $quote->method('getBaseGrandTotal')->willReturn(100.00);
        $quote->method('getBaseCurrencyCode')->willReturn('USD');

        $fresh = $this->makeFreshQuote();
        $freshPayment = $fresh->getPayment();

        $this->v2->expects($this->once())->method('releasePayment');
        // The release is stamped on the payment so a repeat visit cannot resubmit against it -
        // on the quote as committed, not on the one the rolled-back submitQuote() mutated.
        $this->cartRepository->expects($this->once())->method('get')->with(10)->willReturn($fresh);
        $payment->expects($this->never())->method('setAdditionalInformation');
        $freshPayment->expects($this->once())
            ->method('setAdditionalInformation')
            ->with(OrderRecoveryService::KEY_AUTH_RELEASED_AT, $this->isInt());
        $this->cartRepository->expects($this->once())->method('save')->with($fresh);
        $this->redirect->expects($this->once())->method('setPath')->with(self::CART_PATH);

        $this->assertSame($this->redirect, $this->controller->execute());
    }

    /**
     * AlreadyExistsException extends LocalizedException, so a "localized means shopper-facing"
     * test alone hands the shopper the database constraint name for the very failure this
     * controller exists to handle.
     */
    public function testCollisionTextIsNotEchoedToTheShopperDespiteBeingLocalized()
    {
        $quote = $this->makeQuote();
        $this->checkoutSession->method('getQuote')->willReturn($quote);
        // The conflict names no increment ID, so placeOrder() cannot attribute it to this
        // checkout and rethrows to execute(), where the shopper-facing message is chosen.
        $quote->method('getReservedOrderId')->willReturn('000000123');
        $quote->method('getId')->willReturn(10);
        $quote->method('getStoreId')->willReturn(3);
        $quote->method('getPayment')->willReturn(null);
        $this->request->method('getParam')->with('customer-uuid')->willReturn(null);
        $this->customerSession->method('isLoggedIn')->willReturn(true);

        $emptyOrder = $this->makeOrder();
        $emptyOrder->method('loadByIncrementIdAndStoreId')->willReturnSelf();
        $emptyOrder->method('getId')->willReturn(null);
        $this->orderFactory->method('create')->willReturn($emptyOrder);

        $this->cartManagement->expects($this->once())
            ->method('placeOrder')->with(10)
            ->willThrowException(new AlreadyExistsException(
                __('Unique constraint violation found, rule name is UNIQUE_SALES_ORDER_INCREMENT_ID_STORE_ID')
            ));

        $this->messageManager->expects($this->once())
            ->method('addErrorMessage')
            ->with($this->callback(static function ($message) {
                $text = (string)$message;

                return !str_contains($text, 'Unique constraint')
                    && !str_contains($text, 'UNIQUE_SALES_ORDER');
            }));

        $this->redirect->expects($this->once())->method('setPath')->with(self::CART_PATH);

        $this->assertSame($this->redirect, $this->controller->execute());
    }

    /**
     * logSezzleActions() is gated on payment/sezzlepay/log_tracker, and the catch does not
     * rethrow, so with the tracker off this would otherwise be a checkout that failed after
     * authorization and left no trace anywhere - no error report, nothing for an APM.
     */
    public function testPlacementFailureIsAlsoLoggedOutsideTheGatedSezzleLog()
    {
        $quote = $this->makeQuote();
        $this->checkoutSession->method('getQuote')->willReturn($quote);
        $quote->method('getReservedOrderId')->willReturn(null);
        $quote->method('getId')->willReturn(10);
        $quote->method('getPayment')->willReturn(null);
        $this->request->method('getParam')->with('customer-uuid')->willReturn(null);
        $this->customerSession->method('isLoggedIn')->willReturn(true);

        $failure = new \RuntimeException('Some internal failure');
        $this->cartManagement->expects($this->once())
            ->method('placeOrder')->with(10)->willThrowException($failure);

        $this->helper->expects($this->once())
            ->method('logCriticalFailure')
            ->with($this->isString(), $failure);

        $this->redirect->expects($this->once())->method('setPath')->with(self::CART_PATH);

        $this->assertSame($this->redirect, $this->controller->execute());
    }

    /**
     * The fallback inside the logging catch goes through the same sink that just failed, and
     * that sink only swallows NoSuchEntityException and InputException. Anything else would
     * escape logPlacementFailure(), escape execute()'s catch, and cost the shopper the
     * authorization release that runs after it.
     */
    public function testALoggingSinkThatKeepsFailingDoesNotCostTheAuthorizationRelease()
    {
        $quote = $this->makeQuote();
        $this->checkoutSession->method('getQuote')->willReturn($quote);
        $quote->method('getReservedOrderId')->willReturn(null);
        $quote->method('getId')->willReturn(10);
        $this->request->method('getParam')->with('customer-uuid')->willReturn(null);
        $this->customerSession->method('isLoggedIn')->willReturn(true);

        // Every call to the Sezzle log throws - including the fallback inside the logging
        // catch, which goes through the same sink. The first failing line is the one at the
        // top of execute(), so placement is never even reached.
        $this->helper->method('logSezzleActions')
            ->willThrowException(new \RuntimeException('Log sink unavailable'));

        $payment = $this->createMock(QuotePayment::class);
        $payment->method('getAdditionalInformation')->willReturnMap([
            [AuthorizeCommand::KEY_ORIGINAL_ORDER_UUID, 'order-uuid-123'],
            [OrderRecoveryService::KEY_AUTH_RELEASED_AT, null]
        ]);
        $quote->method('getPayment')->willReturn($payment);
        $quote->method('getBaseGrandTotal')->willReturn(100.00);
        $quote->method('getBaseCurrencyCode')->willReturn('USD');
        $quote->method('getStoreId')->willReturn(1);

        // The release still happens, and the shopper still gets a redirect rather than a raw
        // Magento error report page.
        $this->cartRepository->method('get')->with(10)->willReturn($this->makeFreshQuote());
        $this->v2->expects($this->once())->method('releasePayment');
        $this->redirect->expects($this->once())->method('setPath')->with(self::CART_PATH);

        $this->assertSame($this->redirect, $this->controller->execute());
    }

    public function testInternalFailureIsNotEchoedToTheShopper()
    {
        $quote = $this->makeQuote();
        $this->checkoutSession->method('getQuote')->willReturn($quote);
        $quote->method('getReservedOrderId')->willReturn(null);
        $quote->method('getId')->willReturn(10);
        $this->request->method('getParam')->with('customer-uuid')->willReturn(null);
        $this->customerSession->method('isLoggedIn')->willReturn(true);

        // Not a collision, so placeOrder() rethrows and execute() handles it.
        $this->cartManagement->expects($this->once())
            ->method('placeOrder')->with(10)
            ->willThrowException(new \RuntimeException(
                'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry'
            ));

        // A raw database error must never reach the storefront.
        $this->messageManager->expects($this->once())
            ->method('addErrorMessage')
            ->with($this->callback(static function ($message) {
                return !str_contains((string)$message, 'SQLSTATE');
            }));

        $this->redirect->expects($this->once())->method('setPath')->with(self::CART_PATH);

        $this->assertSame($this->redirect, $this->controller->execute());
    }

    public function testLocalizedFailureKeepsItsShopperFacingMessage()
    {
        $quote = $this->makeQuote();
        $this->checkoutSession->method('getQuote')->willReturn($quote);
        $quote->method('getReservedOrderId')->willReturn(null);
        $quote->method('getId')->willReturn(10);
        $this->request->method('getParam')->with('customer-uuid')->willReturn(null);
        $this->customerSession->method('isLoggedIn')->willReturn(true);

        $this->cartManagement->expects($this->once())
            ->method('placeOrder')->with(10)
            ->willThrowException(new LocalizedException(__('Please specify a shipping method.')));

        // Localized messages are written for shoppers, so they are still shown verbatim.
        $this->messageManager->expects($this->once())
            ->method('addErrorMessage')->with('Please specify a shipping method.');

        $this->redirect->expects($this->once())->method('setPath')->with(self::CART_PATH);

        $this->assertSame($this->redirect, $this->controller->execute());
    }

    public function testAuthorizationIsStillReleasedWhenTheOrderLookupFails()
    {
        $quote = $this->makeQuote();
        $this->checkoutSession->method('getQuote')->willReturn($quote);
        $quote->method('getReservedOrderId')->willReturn('000000123');
        $quote->method('getId')->willReturn(10);
        $quote->method('getStoreId')->willReturn(3);
        $this->request->method('getParam')->with('customer-uuid')->willReturn(null);
        $this->customerSession->method('isLoggedIn')->willReturn(true);

        // The idempotency pre-check finds nothing, then the post-failure lookup itself breaks.
        $emptyOrder = $this->makeOrder();
        $emptyOrder->method('loadByIncrementIdAndStoreId')->willReturnSelf();
        $emptyOrder->method('getId')->willReturn(null);

        $createCalls = 0;
        $this->orderFactory->method('create')->willReturnCallback(
            function () use (&$createCalls, $emptyOrder) {
                if (++$createCalls === 1) {
                    return $emptyOrder;
                }
                throw new \RuntimeException('MySQL server has gone away');
            }
        );

        $this->cartManagement->method('placeOrder')
            ->willThrowException(new LocalizedException(__('Some failure')));

        $payment = $this->createMock(QuotePayment::class);
        $payment->method('getAdditionalInformation')->willReturnMap([
            [AuthorizeCommand::KEY_ORIGINAL_ORDER_UUID, 'order-uuid-123'],
            [OrderRecoveryService::KEY_AUTH_RELEASED_AT, null]
        ]);
        $quote->method('getPayment')->willReturn($payment);
        $quote->method('getBaseGrandTotal')->willReturn(100.00);
        $quote->method('getBaseCurrencyCode')->willReturn('USD');

        // A broken lookup must not cost the shopper the release of their authorization.
        $this->cartRepository->method('get')->with(10)->willReturn($this->makeFreshQuote());
        $this->v2->expects($this->once())
            ->method('releasePayment')->with('order-uuid-123', 10000, 'USD', 3);

        $this->redirect->expects($this->once())->method('setPath')->with(self::CART_PATH);

        $this->assertSame($this->redirect, $this->controller->execute());
    }

    public function testPlacementFailureLogsTheFullExceptionChain()
    {
        $quote = $this->makeQuote();
        $this->checkoutSession->method('getQuote')->willReturn($quote);
        $quote->method('getReservedOrderId')->willReturn('000000123');
        $quote->method('getId')->willReturn(10);
        $quote->method('getStoreId')->willReturn(3);
        $this->request->method('getParam')->with('customer-uuid')->willReturn(null);
        $this->customerSession->method('isLoggedIn')->willReturn(true);

        $emptyOrder = $this->makeOrder();
        $emptyOrder->method('loadByIncrementIdAndStoreId')->willReturnSelf();
        $emptyOrder->method('getId')->willReturn(null);
        $this->orderFactory->method('create')->willReturn($emptyOrder);

        $this->cartManagement->method('placeOrder')->willThrowException(
            new \RuntimeException('Outer detail', 0, new \LogicException('The root cause'))
        );

        $logged = [];
        $this->helper->method('logSezzleActions')->willReturnCallback(
            static function ($data) use (&$logged) {
                if (is_array($data)) {
                    $logged[] = $data;
                }
            }
        );

        $this->controller->execute();

        $failures = array_values(array_filter($logged, static function ($entry) {
            return isset($entry['exception_chain']);
        }));
        $this->assertCount(1, $failures, 'the failure should be logged exactly once');

        $entry = $failures[0];
        $this->assertSame(10, $entry['quote_id']);
        $this->assertSame(3, $entry['store_id']);
        $this->assertSame('000000123', $entry['reserved_order_id']);
        $this->assertFalse($entry['is_increment_id_collision']);
        // The whole chain is recorded - the root cause is otherwise never written down anywhere.
        $this->assertSame(
            [\RuntimeException::class, \LogicException::class],
            array_column($entry['exception_chain'], 'class')
        );
        $this->assertSame('The root cause', $entry['exception_chain'][1]['message']);
    }
}

/**
 * Test double exposing Magento\Checkout\Model\Session's magic Last* setters as real methods so
 * they can be mocked under PHPUnit 12, where MockBuilder::addMethods() was removed.
 */
class CheckoutSessionStub extends CheckoutSession
{
    public function setLastQuoteId($quoteId)
    {
        return $this;
    }

    public function setLastSuccessQuoteId($quoteId)
    {
        return $this;
    }

    public function setLastOrderId($orderId)
    {
        return $this;
    }

    public function setLastRealOrderId($realOrderId)
    {
        return $this;
    }

    public function setLastOrderStatus($status)
    {
        return $this;
    }
}

/**
 * Test double exposing Magento\Quote\Model\Quote's magic getBase* getters as real methods.
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
