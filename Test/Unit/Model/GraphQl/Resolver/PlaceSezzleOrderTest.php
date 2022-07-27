<?php

namespace Sezzle\Sezzlepay\Test\Unit\Model\GraphQl\Resolver;

use Magento\Framework\Exception\InvalidArgumentException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\GraphQl\Model\Query\ContextInterface;
use Sezzle\Sezzlepay\Api\CartManagementInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\PaymentMethodManagementInterface;
use Magento\Quote\Model\Quote;
use Magento\QuoteGraphQl\Model\Cart\CheckCartCheckoutAllowance;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
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
     * Mock validator
     *
     * @var Validator|MockObject
     */
    private $validator;

    /**
     * Mock getCartForUser
     *
     * @var GetCartForUser|MockObject
     */
    private $getCartForUser;

    /**
     * Mock checkCartCheckoutAllowance
     *
     * @var CheckCartCheckoutAllowance|MockObject
     */
    private $checkCartCheckoutAllowance;

    /**
     * Mock cartManagement
     *
     * @var CartManagementInterface|MockObject
     */
    private $cartManagement;

    /**
     * Mock orderRepository
     *
     * @var OrderRepositoryInterface|MockObject
     */
    private $orderRepository;

    /**
     * Mock paymentMethodManagement
     *
     * @var PaymentMethodManagementInterface|MockObject
     */
    private $paymentMethodManagement;

    /**
     * Object Manager instance
     *
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * Object to test
     *
     * @var PlaceSezzleOrder
     */
    private $resolver;

    /**
     * Main set up method
     */
    public function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->fieldMock = $this->getMockBuilder(Field::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextMock = $this->getMockBuilder(ContextInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->resolveInfoMock = $this->getMockBuilder(ResolveInfo::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->validator = $this->createMock(Validator::class);
        $this->getCartForUser = $this->createMock(GetCartForUser::class);
        $this->checkCartCheckoutAllowance = $this->createMock(CheckCartCheckoutAllowance::class);
        $this->cartManagement = $this->createMock(CartManagementInterface::class);
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->paymentMethodManagement = $this->createMock(PaymentMethodManagementInterface::class);
        $this->resolver = $this->objectManager->getObject(
            PlaceSezzleOrder::class,
            [
                'validator' => $this->validator,
                'getCartForUser' => $this->getCartForUser,
                'checkCartCheckoutAllowance' => $this->checkCartCheckoutAllowance,
                'cartManagement' => $this->cartManagement,
                'orderRepository' => $this->orderRepository,
                'paymentMethodManagement' => $this->paymentMethodManagement,
            ]
        );
    }

    /**
     * @return array
     */
    public function dataProviderForTestResolve()
    {
        return [
            'Testcase 1' => [
                'prerequisites' => ['param' => 1],
                'expectedResult' => ['param' => 1]
            ]
        ];
    }

    public function testSezzleNotEnabled()
    {
        $exceptionMessage = 'Sezzle payment method is not enabled.';
        $this->expectException('Magento\Framework\GraphQl\Exception\GraphQlInputException');
        $this->expectExceptionMessage($exceptionMessage);

        $this->validator->expects($this->once())
            ->method('validateInput')
            ->willThrowException(new GraphQlInputException(__($exceptionMessage)));


        $this->resolver->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock
        );
    }

    public function testCartIdMissing()
    {
        $exceptionMessage = 'Required parameter "cart_id" is missing.';
        $this->expectException('Magento\Framework\Exception\InvalidArgumentException');
        $this->expectExceptionMessage($exceptionMessage);

        $this->validator->expects($this->once())
            ->method('validateInput')
            ->willThrowException(new InvalidArgumentException(__($exceptionMessage)));


        $this->resolver->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock
        );
    }

    public function testCartNotFound()
    {
        $cartHash = 'abcd1234';
        $exceptionMessage = sprintf('Could not find a cart with ID "%s"', $cartHash);
        $this->expectException('Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException');
        $this->expectExceptionMessage($exceptionMessage);

        $this->validator->expects($this->once())
            ->method('validateInput');

        $this->getCartForUser->expects($this->once())
            ->method('getCart')
            ->willThrowException(new GraphQlNoSuchEntityException(
                __('Could not find a cart with ID "%masked_cart_id"', ['masked_cart_id' => $cartHash])
            ));


        $this->resolver->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            ['input' => ['cart_id' => $cartHash]]
        );
    }

    public function testGuestCheckoutNotAllowed()
    {
        $cartHash = 'abcd1234';
        $exceptionMessage = 'Guest checkout is not allowed. ' .
            'Register a customer account or login with existing one.';
        $this->expectException('Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException');
        $this->expectExceptionMessage($exceptionMessage);

        $this->validator->expects($this->once())
            ->method('validateInput');

        $quoteMock = $this->getMockBuilder(Quote::class)
            ->setMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->getCartForUser->expects($this->once())
            ->method('getCart')
            ->willReturn($quoteMock);

        $this->checkCartCheckoutAllowance->expects($this->once())
            ->method('execute')
            ->with($quoteMock)
            ->willThrowException(new GraphQlAuthorizationException(
                __($exceptionMessage)
            ));

        $this->resolver->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            ['input' => ['cart_id' => $cartHash]]
        );
    }

    public function testGuestEmailMissing()
    {
        $cartHash = 'abcd1234';
        $exceptionMessage = 'Guest email for cart is missing.';
        $this->expectException('Magento\Framework\GraphQl\Exception\GraphQlInputException');
        $this->expectExceptionMessage($exceptionMessage);

        $this->validator->expects($this->once())
            ->method('validateInput');

        $quoteMock = $this->getMockBuilder(Quote::class)
            ->setMethods(['getCustomerEmail'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->getCartForUser->expects($this->once())
            ->method('getCart')
            ->willReturn($quoteMock);

        $this->checkCartCheckoutAllowance->expects($this->once())
            ->method('execute')
            ->with($quoteMock);

        $this->contextMock->expects($this->once())
            ->method('getUserId')
            ->willReturn(0);

        $quoteMock->expects($this->once())
            ->method('getCustomerEmail')
            ->willReturn('');


        $this->resolver->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            ['input' => ['cart_id' => $cartHash]]
        );
    }

    public function testQuoteNotFound()
    {
        $cartHash = 'abcd1234';
        $cartId = 1;
        $exceptionMessage = 'Quote not found.';
        $this->expectException('Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException');
        $this->expectExceptionMessage($exceptionMessage);

        $this->validator->expects($this->once())
            ->method('validateInput');

        $quoteMock = $this->getMockBuilder(Quote::class)
            ->setMethods(['getCustomerEmail', 'setCheckoutMethod', 'getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->getCartForUser->expects($this->once())
            ->method('getCart')
            ->willReturn($quoteMock);

        $this->checkCartCheckoutAllowance->expects($this->once())
            ->method('execute')
            ->with($quoteMock);

        $this->contextMock->expects($this->once())
            ->method('getUserId')
            ->willReturn(0);

        $quoteMock->expects($this->once())
            ->method('getCustomerEmail')
            ->willReturn('guest@test.com');

        $quoteMock->expects($this->once())
            ->method('setCheckoutMethod')
            ->with(CartManagementInterface::METHOD_GUEST);

        $quoteMock->expects($this->once())
            ->method('getId')
            ->willReturn($cartId);

        $this->paymentMethodManagement->expects($this->once())
            ->method('get')
            ->with($cartId)
            ->willThrowException(new NoSuchEntityException(
                __($exceptionMessage)
            ));

        $this->resolver->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            ['input' => ['cart_id' => $cartHash]]
        );
    }

    public function testOrderValidationFailed()
    {
        $cartHash = 'abcd1234';
        $cartId = 1;
        $exceptionMessage = 'Unable to place Sezzle order: Failed order validation.';
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->expectExceptionMessage($exceptionMessage);

        $this->validator->expects($this->once())
            ->method('validateInput');

        $quoteMock = $this->getMockBuilder(Quote::class)
            ->setMethods(['getCustomerEmail', 'setCheckoutMethod', 'getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->getCartForUser->expects($this->once())
            ->method('getCart')
            ->willReturn($quoteMock);

        $this->checkCartCheckoutAllowance->expects($this->once())
            ->method('execute')
            ->with($quoteMock);

        $this->contextMock->expects($this->once())
            ->method('getUserId')
            ->willReturn(0);

        $quoteMock->expects($this->once())
            ->method('getCustomerEmail')
            ->willReturn('guest@test.com');

        $quoteMock->expects($this->once())
            ->method('setCheckoutMethod')
            ->with(CartManagementInterface::METHOD_GUEST);

        $quoteMock->expects($this->once())
            ->method('getId')
            ->willReturn($cartId);

        $paymentMock = $this->getMockBuilder(PaymentInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->paymentMethodManagement->expects($this->once())
            ->method('get')
            ->with($cartId)
            ->willReturn($paymentMock);

        $this->cartManagement->expects($this->once())
            ->method('placeOrder')
            ->with($cartId, $paymentMock)
            ->willThrowException(new LocalizedException(
                __('Failed order validation.')
            ));

        $this->resolver->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            ['input' => ['cart_id' => $cartHash]]
        );
    }

    public function testOrderNotFound()
    {
        $cartHash = 'abcd1234';
        $cartId = 1;
        $orderId = 4;
        $exceptionMessage = 'The entity that was requested doesn\'t exist. Verify the entity and try again.';
        $this->expectException('Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException');
        $this->expectExceptionMessage($exceptionMessage);

        $this->validator->expects($this->once())
            ->method('validateInput');

        $quoteMock = $this->getMockBuilder(Quote::class)
            ->setMethods(['getCustomerEmail', 'setCheckoutMethod', 'getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->getCartForUser->expects($this->once())
            ->method('getCart')
            ->willReturn($quoteMock);

        $this->checkCartCheckoutAllowance->expects($this->once())
            ->method('execute')
            ->with($quoteMock);

        $this->contextMock->expects($this->once())
            ->method('getUserId')
            ->willReturn(0);

        $quoteMock->expects($this->once())
            ->method('getCustomerEmail')
            ->willReturn('guest@test.com');

        $quoteMock->expects($this->once())
            ->method('setCheckoutMethod')
            ->with(CartManagementInterface::METHOD_GUEST);

        $quoteMock->expects($this->once())
            ->method('getId')
            ->willReturn($cartId);

        $paymentMock = $this->getMockBuilder(PaymentInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->paymentMethodManagement->expects($this->once())
            ->method('get')
            ->with($cartId)
            ->willReturn($paymentMock);

        $this->cartManagement->expects($this->once())
            ->method('placeOrder')
            ->with($cartId, $paymentMock)
            ->willReturn($orderId);

        $this->orderRepository->expects($this->once())
            ->method('get')
            ->with($orderId)
            ->willThrowException(new NoSuchEntityException(
                __($exceptionMessage)
            ));

        $this->resolver->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            ['input' => ['cart_id' => $cartHash]]
        );
    }

    public function testPlaceGuestOrderSuccess()
    {
        $cartHash = 'abcd1234';
        $cartId = 1;
        $orderId = 4;
        $orderNumber = '11112222';

        $this->validator->expects($this->once())
            ->method('validateInput');

        $quoteMock = $this->getMockBuilder(Quote::class)
            ->setMethods(['getCustomerEmail', 'setCheckoutMethod', 'getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->getCartForUser->expects($this->once())
            ->method('getCart')
            ->willReturn($quoteMock);

        $this->checkCartCheckoutAllowance->expects($this->once())
            ->method('execute')
            ->with($quoteMock);

        $this->contextMock->expects($this->once())
            ->method('getUserId')
            ->willReturn(0);

        $quoteMock->expects($this->once())
            ->method('getCustomerEmail')
            ->willReturn('guest@test.com');

        $quoteMock->expects($this->once())
            ->method('setCheckoutMethod')
            ->with(CartManagementInterface::METHOD_GUEST);

        $quoteMock->expects($this->once())
            ->method('getId')
            ->willReturn($cartId);

        $paymentMock = $this->getMockBuilder(PaymentInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->paymentMethodManagement->expects($this->once())
            ->method('get')
            ->with($cartId)
            ->willReturn($paymentMock);

        $this->cartManagement->expects($this->once())
            ->method('placeOrder')
            ->with($cartId, $paymentMock)
            ->willReturn($orderId);

        $orderMock = $this->getMockBuilder(OrderInterface::class)
            ->setMethods(['getIncrementId', 'getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->orderRepository->expects($this->once())
            ->method('get')
            ->with($orderId)
            ->willReturn($orderMock);

        $orderMock->expects($this->once())
            ->method('getIncrementId')
            ->willReturn($orderNumber);

        $this->assertEquals(
            [
                'order' => [
                    'order_number' => $orderNumber,
                    'order_id' => $orderId
                ]
            ],
            $this->resolver->resolve(
                $this->fieldMock,
                $this->contextMock,
                $this->resolveInfoMock,
                null,
                ['input' => ['cart_id' => $cartHash]]
            )
        );
    }

    public function testPlaceRegisteredCustomerOrderSuccess()
    {
        $cartHash = 'abcd1234';
        $cartId = 1;
        $orderId = 4;
        $orderNumber = '11112222';

        $this->validator->expects($this->once())
            ->method('validateInput');

        $quoteMock = $this->getMockBuilder(Quote::class)
            ->setMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->getCartForUser->expects($this->once())
            ->method('getCart')
            ->willReturn($quoteMock);

        $this->checkCartCheckoutAllowance->expects($this->once())
            ->method('execute')
            ->with($quoteMock);

        $this->contextMock->expects($this->once())
            ->method('getUserId')
            ->willReturn(1);

        $quoteMock->expects($this->once())
            ->method('getId')
            ->willReturn($cartId);

        $paymentMock = $this->getMockBuilder(PaymentInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->paymentMethodManagement->expects($this->once())
            ->method('get')
            ->with($cartId)
            ->willReturn($paymentMock);

        $this->cartManagement->expects($this->once())
            ->method('placeOrder')
            ->with($cartId, $paymentMock)
            ->willReturn($orderId);

        $orderMock = $this->getMockBuilder(OrderInterface::class)
            ->setMethods(['getIncrementId', 'getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->orderRepository->expects($this->once())
            ->method('get')
            ->with($orderId)
            ->willReturn($orderMock);

        $orderMock->expects($this->once())
            ->method('getIncrementId')
            ->willReturn($orderNumber);

        $this->assertEquals(
            [
                'order' => [
                    'order_number' => $orderNumber,
                    'order_id' => $orderId
                ]
            ],
            $this->resolver->resolve(
                $this->fieldMock,
                $this->contextMock,
                $this->resolveInfoMock,
                null,
                ['input' => ['cart_id' => $cartHash]]
            )
        );
    }
}
