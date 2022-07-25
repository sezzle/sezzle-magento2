<?php

namespace Sezzle\Sezzlepay\Test\Unit\Model\GraphQl\Resolver;

use Magento\Framework\Exception\InvalidArgumentException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\GraphQl\Model\Query\ContextInterface;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Sezzle\Sezzlepay\Api\CheckoutInterface;
use Sezzle\Sezzlepay\Model\GraphQl\Resolver\CreateSezzleCheckout;
use Sezzle\Sezzlepay\Model\GraphQl\Resolver\GetCartForUser;
use Sezzle\Sezzlepay\Model\GraphQl\Resolver\Validator;

/**
 * @covers \Sezzle\Sezzlepay\Model\GraphQl\Resolver\CreateSezzleCheckout
 */
class CreateSezzleCheckoutTest extends TestCase
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
     * Mock checkout
     *
     * @var CheckoutInterface|MockObject
     */
    private $checkout;

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
     * Object Manager instance
     *
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * Object to test
     *
     * @var CreateSezzleCheckout
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

        $this->checkout = $this->createMock(CheckoutInterface::class);
        $this->validator = $this->createMock(Validator::class);
        $this->getCartForUser = $this->createMock(GetCartForUser::class);
        $this->resolver = $this->objectManager->getObject(
            CreateSezzleCheckout::class,
            [
                'checkout' => $this->checkout,
                'validator' => $this->validator,
                'getCartForUser' => $this->getCartForUser,
            ]
        );
    }

    /**
     * Test mutation when customer isn't authorized.
     */
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

    /**
     * Test mutation when customer isn't authorized.
     */
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

    /**
     * Test mutation when customer isn't authorized.
     */
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

    /**
     * Test mutation when customer isn't authorized.
     */
    public function testSezzleCheckoutError()
    {
        $cartHash = 'abcd1234';
        $this->expectException('Magento\Framework\GraphQl\Exception\GraphQlInputException');
        $this->expectExceptionMessage('Unable to create Sezzle checkout.');

        $this->validator->expects($this->once())
            ->method('validateInput');

        $quoteMock = $this->getMockBuilder(Quote::class)
            ->setMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->getCartForUser->expects($this->once())
            ->method('getCart')
            ->willReturn($quoteMock);

        $quoteMock->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $this->checkout->expects($this->once())
            ->method('getCheckoutURL')
            ->with(1)
            ->willReturn('');


        $this->resolver->resolve(
            $this->fieldMock,
            $this->contextMock,
            $this->resolveInfoMock,
            null,
            ['input' => ['cart_id' => $cartHash]]
        );
    }

    /**
     * Test mutation when customer isn't authorized.
     */
    public function testSezzleCheckoutSuccess()
    {
        $cartHash = 'abcd1234';
        $cartId = 1;
        $checkoutURL = 'https://magento-test.sezzle.com/?id=1234';

        $this->validator->expects($this->once())
            ->method('validateInput');

        $quoteMock = $this->getMockBuilder(Quote::class)
            ->setMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->getCartForUser->expects($this->once())
            ->method('getCart')
            ->willReturn($quoteMock);

        $quoteMock->expects($this->once())
            ->method('getId')
            ->willReturn($cartId);

        $this->checkout->expects($this->once())
            ->method('getCheckoutURL')
            ->with($cartId)
            ->willReturn($checkoutURL);

        $this->assertEquals(
            [
                'checkout_url' => $checkoutURL
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
