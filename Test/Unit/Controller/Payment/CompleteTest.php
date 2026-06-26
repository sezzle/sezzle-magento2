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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sezzle\Sezzlepay\Api\CartManagementInterface;
use Sezzle\Sezzlepay\Api\GuestCartManagementInterface;
use Sezzle\Sezzlepay\Api\V2Interface;
use Sezzle\Sezzlepay\Controller\Payment\Complete;
use Sezzle\Sezzlepay\Gateway\Command\AuthorizeCommand;
use Sezzle\Sezzlepay\Helper\Data;
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
                'v2' => $this->v2,
            ]
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
     * @return Order|MockObject
     */
    private function makeOrder()
    {
        return $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['loadByIncrementId', 'getId', 'getQuoteId', 'getIncrementId', 'getStatus'])
            ->getMock();
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

        $order = $this->makeOrder();
        $order->method('loadByIncrementId')->with('000000123')->willReturnSelf();
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
        $this->request->method('getParam')->with('customer-uuid')->willReturn(null);
        $this->customerSession->method('isLoggedIn')->willReturn(true);

        // First lookup (idempotency guard) finds nothing; placeOrder then collides because a
        // concurrent request placed the order; the second lookup finds it.
        $emptyOrder = $this->makeOrder();
        $emptyOrder->method('loadByIncrementId')->willReturnSelf();
        $emptyOrder->method('getId')->willReturn(null);

        $foundOrder = $this->makeOrder();
        $foundOrder->method('loadByIncrementId')->with('000000123')->willReturnSelf();
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
        $this->request->method('getParam')->with('customer-uuid')->willReturn(null);
        $this->customerSession->method('isLoggedIn')->willReturn(true);

        // Both lookups find no order for this quote: the increment id was consumed by an
        // unrelated order, so a fresh id must be reserved and placeOrder retried once.
        $emptyOrder = $this->makeOrder();
        $emptyOrder->method('loadByIncrementId')->willReturnSelf();
        $emptyOrder->method('getId')->willReturn(null);
        $this->orderFactory->method('create')->willReturn($emptyOrder);

        $calls = 0;
        $this->cartManagement->expects($this->exactly(2))
            ->method('placeOrder')->with(10)
            ->willReturnCallback(function () use (&$calls) {
                if (++$calls === 1) {
                    throw new AlreadyExistsException(__('Unique constraint violation found'));
                }
                return 200;
            });

        // A fresh reserved id is generated and persisted before the retry.
        $quote->expects($this->once())->method('setReservedOrderId')->with(null);
        $quote->expects($this->once())->method('reserveOrderId')->willReturnSelf();
        $this->cartRepository->expects($this->once())->method('save')->with($quote);
        $this->v2->expects($this->never())->method('releasePayment');

        $this->redirect->expects($this->once())->method('setPath')->with(self::SUCCESS_PATH);

        $this->assertSame($this->redirect, $this->controller->execute());
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
