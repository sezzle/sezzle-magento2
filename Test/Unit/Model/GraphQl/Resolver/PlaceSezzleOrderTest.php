<?php

namespace Sezzle\Sezzlepay\Test\Unit\Model\GraphQl\Resolver;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\GraphQl\Model\Query\ContextInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Sezzle\Sezzlepay\Api\CartManagementInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\PaymentMethodManagementInterface;
use Magento\Quote\Model\Quote;
use Magento\QuoteGraphQl\Model\Cart\CheckCartCheckoutAllowance;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Sezzle\Sezzlepay\Api\V2Interface;
use Sezzle\Sezzlepay\Helper\Data;
use Sezzle\Sezzlepay\Model\GraphQl\Resolver\GetCartForUser;
use Sezzle\Sezzlepay\Model\GraphQl\Resolver\PlaceSezzleOrder;
use Sezzle\Sezzlepay\Model\GraphQl\Resolver\Validator;

/**
 * @covers \Sezzle\Sezzlepay\Model\GraphQl\Resolver\PlaceSezzleOrder
 */
class PlaceSezzleOrderTest extends TestCase
{

    /**
     * @var ContextInterface|MockObject
     */
    private $contextMock;

    /**
     * @var Field|MockObject
     */
    private $fieldMock;

    /**
     * @var ResolveInfo|MockObject
     */
    private $resolveInfoMock;

    /**
     * @var Validator|MockObject
     */
    private $validator;

    /**
     * @var GetCartForUser|MockObject
     */
    private $getCartForUser;

    /**
     * @var CheckCartCheckoutAllowance|MockObject
     */
    private $checkCartCheckoutAllowance;

    /**
     * @var CartManagementInterface|MockObject
     */
    private $cartManagement;

    /**
     * @var OrderRepositoryInterface|MockObject
     */
    private $orderRepository;

    /**
     * @var PaymentMethodManagementInterface|MockObject
     */
    private $paymentMethodManagement;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private $cartRepository;

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
     * @var PlaceSezzleOrder
     */
    private $resolver;

    /**
     * Main set up method
     */
    public function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->fieldMock = $this->createMock(Field::class);
        $this->contextMock = $this->createMock(ContextInterface::class);
        $this->resolveInfoMock = $this->createMock(ResolveInfo::class);

        $this->validator = $this->createMock(Validator::class);
        $this->getCartForUser = $this->createMock(GetCartForUser::class);
        $this->checkCartCheckoutAllowance = $this->createMock(CheckCartCheckoutAllowance::class);
        $this->cartManagement = $this->createMock(CartManagementInterface::class);
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->paymentMethodManagement = $this->createMock(PaymentMethodManagementInterface::class);
        $this->cartRepository = $this->createMock(CartRepositoryInterface::class);
        $this->orderFactory = $this->getMockBuilder(OrderFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->v2 = $this->createMock(V2Interface::class);
        $this->helper = $this->createMock(Data::class);

        $this->resolver = $this->objectManager->getObject(
            PlaceSezzleOrder::class,
            [
                'validator' => $this->validator,
                'getCartForUser' => $this->getCartForUser,
                'checkCartCheckoutAllowance' => $this->checkCartCheckoutAllowance,
                'cartManagement' => $this->cartManagement,
                'orderRepository' => $this->orderRepository,
                'paymentMethodManagement' => $this->paymentMethodManagement,
                'cartRepository' => $this->cartRepository,
                'orderFactory' => $this->orderFactory,
                'v2' => $this->v2,
                'helper' => $this->helper,
            ]
        );
    }

    /**
     * @return Quote|MockObject
     */
    private function makeQuote()
    {
        return $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getId', 'setCheckoutMethod', 'getReservedOrderId',
                'setReservedOrderId', 'reserveOrderId', 'getPayment', 'getStoreId'
            ])
            ->addMethods(['getCustomerEmail', 'getBaseGrandTotal', 'getBaseCurrencyCode'])
            ->getMock();
    }

    /**
     * @return Order|MockObject
     */
    private function makeOrder()
    {
        return $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['loadByIncrementId', 'getId', 'getQuoteId', 'getIncrementId'])
            ->getMock();
    }

    private function resolve(string $cartHash)
    {
        return $this->resolver->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            ['input' => ['cart_id' => $cartHash]]
        );
    }

    public function testCartNotFound()
    {
        $cartHash = 'abcd1234';
        $exceptionMessage = sprintf('Could not find a cart with ID "%s"', $cartHash);
        $this->expectException(GraphQlNoSuchEntityException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $this->validator->expects($this->once())->method('validateInput');
        $this->getCartForUser->expects($this->once())
            ->method('getCart')
            ->willThrowException(new GraphQlNoSuchEntityException(
                __('Could not find a cart with ID "%masked_cart_id"', ['masked_cart_id' => $cartHash])
            ));

        $this->resolve($cartHash);
    }

    public function testGuestCheckoutNotAllowed()
    {
        $cartHash = 'abcd1234';
        $exceptionMessage = 'Guest checkout is not allowed. ' .
            'Register a customer account or login with existing one.';
        $this->expectException(GraphQlAuthorizationException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $this->validator->expects($this->once())->method('validateInput');

        $quoteMock = $this->makeQuote();
        $this->getCartForUser->expects($this->once())->method('getCart')->willReturn($quoteMock);
        $this->checkCartCheckoutAllowance->expects($this->once())
            ->method('execute')
            ->with($quoteMock)
            ->willThrowException(new GraphQlAuthorizationException(__($exceptionMessage)));

        $this->resolve($cartHash);
    }

    public function testGuestEmailMissing()
    {
        $cartHash = 'abcd1234';
        $exceptionMessage = 'Guest email for cart is missing.';
        $this->expectException('Magento\Framework\GraphQl\Exception\GraphQlInputException');
        $this->expectExceptionMessage($exceptionMessage);

        $this->validator->expects($this->once())->method('validateInput');

        $quoteMock = $this->makeQuote();
        $this->getCartForUser->expects($this->once())->method('getCart')->willReturn($quoteMock);
        $this->checkCartCheckoutAllowance->expects($this->once())->method('execute')->with($quoteMock);
        $this->contextMock->expects($this->once())->method('getUserId')->willReturn(0);
        $quoteMock->method('getCustomerEmail')->willReturn('');

        $this->resolve($cartHash);
    }

    public function testQuoteNotFound()
    {
        $cartHash = 'abcd1234';
        $cartId = 1;
        $exceptionMessage = 'Quote not found.';
        $this->expectException(GraphQlNoSuchEntityException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $this->validator->expects($this->once())->method('validateInput');

        $quoteMock = $this->makeQuote();
        $this->getCartForUser->expects($this->once())->method('getCart')->willReturn($quoteMock);
        $this->checkCartCheckoutAllowance->expects($this->once())->method('execute')->with($quoteMock);
        $this->contextMock->expects($this->once())->method('getUserId')->willReturn(0);
        $quoteMock->method('getCustomerEmail')->willReturn('guest@test.com');
        $quoteMock->expects($this->once())->method('setCheckoutMethod')
            ->with(CartManagementInterface::METHOD_GUEST);
        $quoteMock->method('getId')->willReturn($cartId);
        $quoteMock->method('getReservedOrderId')->willReturn(null);

        $this->paymentMethodManagement->expects($this->once())
            ->method('get')
            ->with($cartId)
            ->willThrowException(new NoSuchEntityException(__($exceptionMessage)));

        $this->resolve($cartHash);
    }

    public function testOrderValidationFailedReleasesNothingWithoutUuid()
    {
        $cartHash = 'abcd1234';
        $cartId = 1;
        $exceptionMessage = 'Unable to place Sezzle order: Failed order validation.';
        $this->expectException('Magento\Framework\GraphQl\Exception\GraphQlInputException');
        $this->expectExceptionMessage($exceptionMessage);

        $this->validator->expects($this->once())->method('validateInput');

        $quoteMock = $this->makeQuote();
        $this->getCartForUser->expects($this->once())->method('getCart')->willReturn($quoteMock);
        $this->checkCartCheckoutAllowance->expects($this->once())->method('execute')->with($quoteMock);
        $this->contextMock->expects($this->once())->method('getUserId')->willReturn(0);
        $quoteMock->method('getCustomerEmail')->willReturn('guest@test.com');
        $quoteMock->expects($this->once())->method('setCheckoutMethod')
            ->with(CartManagementInterface::METHOD_GUEST);
        $quoteMock->method('getId')->willReturn($cartId);
        $quoteMock->method('getReservedOrderId')->willReturn(null);
        // No Sezzle order UUID on the payment => release safety net is a no-op.
        $quoteMock->method('getPayment')->willReturn(null);

        $paymentMock = $this->createMock(PaymentInterface::class);
        $this->paymentMethodManagement->expects($this->once())
            ->method('get')->with($cartId)->willReturn($paymentMock);

        $this->cartManagement->expects($this->once())
            ->method('placeOrder')
            ->with($cartId, $paymentMock)
            ->willThrowException(new LocalizedException(__('Failed order validation.')));

        $this->v2->expects($this->never())->method('releasePayment');

        $this->resolve($cartHash);
    }

    public function testOrderNotFound()
    {
        $cartHash = 'abcd1234';
        $cartId = 1;
        $orderId = 4;
        $exceptionMessage = 'The entity that was requested doesn\'t exist. Verify the entity and try again.';
        $this->expectException(GraphQlNoSuchEntityException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $this->validator->expects($this->once())->method('validateInput');

        $quoteMock = $this->makeQuote();
        $this->getCartForUser->expects($this->once())->method('getCart')->willReturn($quoteMock);
        $this->checkCartCheckoutAllowance->expects($this->once())->method('execute')->with($quoteMock);
        $this->contextMock->expects($this->once())->method('getUserId')->willReturn(0);
        $quoteMock->method('getCustomerEmail')->willReturn('guest@test.com');
        $quoteMock->expects($this->once())->method('setCheckoutMethod')
            ->with(CartManagementInterface::METHOD_GUEST);
        $quoteMock->method('getId')->willReturn($cartId);
        $quoteMock->method('getReservedOrderId')->willReturn(null);

        $paymentMock = $this->createMock(PaymentInterface::class);
        $this->paymentMethodManagement->expects($this->once())
            ->method('get')->with($cartId)->willReturn($paymentMock);
        $this->cartManagement->expects($this->once())
            ->method('placeOrder')->with($cartId, $paymentMock)->willReturn($orderId);
        $this->orderRepository->expects($this->once())
            ->method('get')->with($orderId)
            ->willThrowException(new NoSuchEntityException(__($exceptionMessage)));

        $this->resolve($cartHash);
    }

    public function testPlaceGuestOrderSuccess()
    {
        $cartHash = 'abcd1234';
        $cartId = 1;
        $orderId = 4;
        $orderNumber = '11112222';

        $this->validator->expects($this->once())->method('validateInput');

        $quoteMock = $this->makeQuote();
        $this->getCartForUser->expects($this->once())->method('getCart')->willReturn($quoteMock);
        $this->checkCartCheckoutAllowance->expects($this->once())->method('execute')->with($quoteMock);
        $this->contextMock->expects($this->once())->method('getUserId')->willReturn(0);
        $quoteMock->method('getCustomerEmail')->willReturn('guest@test.com');
        $quoteMock->expects($this->once())->method('setCheckoutMethod')
            ->with(CartManagementInterface::METHOD_GUEST);
        $quoteMock->method('getId')->willReturn($cartId);
        $quoteMock->method('getReservedOrderId')->willReturn(null);

        $paymentMock = $this->createMock(PaymentInterface::class);
        $this->paymentMethodManagement->expects($this->once())
            ->method('get')->with($cartId)->willReturn($paymentMock);
        $this->cartManagement->expects($this->once())
            ->method('placeOrder')->with($cartId, $paymentMock)->willReturn($orderId);

        $orderMock = $this->makeOrder();
        $this->orderRepository->expects($this->once())->method('get')->with($orderId)->willReturn($orderMock);
        $orderMock->method('getIncrementId')->willReturn($orderNumber);
        $orderMock->method('getId')->willReturn($orderId);

        $this->assertEquals(
            ['order' => ['order_number' => $orderNumber, 'order_id' => $orderId]],
            $this->resolve($cartHash)
        );
    }

    public function testPlaceRegisteredCustomerOrderSuccess()
    {
        $cartHash = 'abcd1234';
        $cartId = 1;
        $orderId = 4;
        $orderNumber = '11112222';

        $this->validator->expects($this->once())->method('validateInput');

        $quoteMock = $this->makeQuote();
        $this->getCartForUser->expects($this->once())->method('getCart')->willReturn($quoteMock);
        $this->checkCartCheckoutAllowance->expects($this->once())->method('execute')->with($quoteMock);
        $this->contextMock->expects($this->once())->method('getUserId')->willReturn(1);
        $quoteMock->method('getId')->willReturn($cartId);
        $quoteMock->method('getReservedOrderId')->willReturn(null);

        $paymentMock = $this->createMock(PaymentInterface::class);
        $this->paymentMethodManagement->expects($this->once())
            ->method('get')->with($cartId)->willReturn($paymentMock);
        $this->cartManagement->expects($this->once())
            ->method('placeOrder')->with($cartId, $paymentMock)->willReturn($orderId);

        $orderMock = $this->makeOrder();
        $this->orderRepository->expects($this->once())->method('get')->with($orderId)->willReturn($orderMock);
        $orderMock->method('getIncrementId')->willReturn($orderNumber);
        $orderMock->method('getId')->willReturn($orderId);

        $this->assertEquals(
            ['order' => ['order_number' => $orderNumber, 'order_id' => $orderId]],
            $this->resolve($cartHash)
        );
    }

    /**
     * Idempotency: the order already exists for this cart, so placeOrder is never called.
     */
    public function testReturnsExistingOrderWithoutResubmitting()
    {
        $cartHash = 'abcd1234';
        $cartId = 1;
        $orderEntityId = 7;
        $reservedId = '000000123';

        $this->validator->expects($this->once())->method('validateInput');

        $quoteMock = $this->makeQuote();
        $this->getCartForUser->expects($this->once())->method('getCart')->willReturn($quoteMock);
        $this->checkCartCheckoutAllowance->expects($this->once())->method('execute')->with($quoteMock);
        $this->contextMock->expects($this->once())->method('getUserId')->willReturn(1);
        $quoteMock->method('getId')->willReturn($cartId);
        $quoteMock->method('getReservedOrderId')->willReturn($reservedId);

        $orderMock = $this->makeOrder();
        $orderMock->method('loadByIncrementId')->with($reservedId)->willReturnSelf();
        $orderMock->method('getId')->willReturn($orderEntityId);
        $orderMock->method('getQuoteId')->willReturn($cartId);
        $orderMock->method('getIncrementId')->willReturn($reservedId);
        $this->orderFactory->expects($this->once())->method('create')->willReturn($orderMock);

        $this->cartManagement->expects($this->never())->method('placeOrder');

        $this->assertEquals(
            ['order' => ['order_number' => $reservedId, 'order_id' => $orderEntityId]],
            $this->resolve($cartHash)
        );
    }

    /**
     * A reserved-id collision whose existing order belongs to this cart is treated as success.
     */
    public function testCollisionRecoveredByExistingOrder()
    {
        $cartHash = 'abcd1234';
        $cartId = 1;
        $orderEntityId = 9;
        $reservedId = '000000456';

        $this->validator->expects($this->once())->method('validateInput');

        $quoteMock = $this->makeQuote();
        $this->getCartForUser->expects($this->once())->method('getCart')->willReturn($quoteMock);
        $this->checkCartCheckoutAllowance->expects($this->once())->method('execute')->with($quoteMock);
        $this->contextMock->expects($this->once())->method('getUserId')->willReturn(1);
        $quoteMock->method('getId')->willReturn($cartId);
        $quoteMock->method('getReservedOrderId')->willReturn($reservedId);

        // First lookup (idempotency pre-check) finds nothing; second (after collision) finds it.
        $emptyOrder = $this->makeOrder();
        $emptyOrder->method('loadByIncrementId')->willReturnSelf();
        $emptyOrder->method('getId')->willReturn(null);

        $foundOrder = $this->makeOrder();
        $foundOrder->method('loadByIncrementId')->willReturnSelf();
        $foundOrder->method('getId')->willReturn($orderEntityId);
        $foundOrder->method('getQuoteId')->willReturn($cartId);
        $foundOrder->method('getIncrementId')->willReturn($reservedId);

        $this->orderFactory->expects($this->exactly(2))
            ->method('create')
            ->willReturnOnConsecutiveCalls($emptyOrder, $foundOrder);

        $paymentMock = $this->createMock(PaymentInterface::class);
        $this->paymentMethodManagement->expects($this->once())
            ->method('get')->with($cartId)->willReturn($paymentMock);
        $this->cartManagement->expects($this->once())
            ->method('placeOrder')->with($cartId, $paymentMock)
            ->willThrowException(new AlreadyExistsException(__('Unique constraint violation found')));

        // Recovered from the existing order; no resubmission through orderRepository.
        $this->orderRepository->expects($this->never())->method('get');

        $this->assertEquals(
            ['order' => ['order_number' => $reservedId, 'order_id' => $orderEntityId]],
            $this->resolve($cartHash)
        );
    }
}
