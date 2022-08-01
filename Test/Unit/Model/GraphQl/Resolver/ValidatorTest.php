<?php

namespace Sezzle\Sezzlepay\Test\Unit\Model\GraphQl\Resolver;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\GraphQl\Model\Query\ContextExtensionInterface;
use Magento\GraphQl\Model\Query\ContextInterface;
use Magento\Store\Model\Store;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Sezzle\Sezzlepay\Gateway\Config\Config;
use Sezzle\Sezzlepay\Model\GraphQl\Resolver\Validator;

/**
 * @covers \Sezzle\Sezzlepay\Model\GraphQl\Resolver\Validator
 */
class ValidatorTest extends TestCase
{

    /**
     * @var ContextInterface|MockObject
     */
    private $contextMock;

    /**
     * @var ContextExtensionInterface|MockObject
     */
    private $contextExtensionMock;

    /**
     * @var Store|MockObject
     */
    private $storeMock;

    /**
     * Mock config
     *
     * @var Config|MockObject
     */
    private $config;

    /**
     * Object Manager instance
     *
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * Object to test
     *
     * @var Validator
     */
    private $validator;

    /**
     * Main set up method
     */
    public function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->contextMock = $this->getMockBuilder(ContextInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getExtensionAttributes'])
            ->getMockForAbstractClass();

        $this->contextExtensionMock = $this->getMockBuilder(ContextExtensionInterface::class)
            ->setMethods(['getStore'])
            ->getMockForAbstractClass();

        $this->storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMockForAbstractClass();

        $this->config = $this->createMock(Config::class);
        $this->validator = $this->objectManager->getObject(
            Validator::class,
            [
                'config' => $this->config,
            ]
        );
    }

    public function testSezzleNotEnabled()
    {
        $storeId = 1;
        $exceptionMessage = 'Sezzle payment method is not enabled.';
        $this->expectException('Magento\Framework\GraphQl\Exception\GraphQlInputException');
        $this->expectExceptionMessage($exceptionMessage);

        $this->contextMock
            ->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($this->contextExtensionMock);

        $this->contextExtensionMock
            ->expects($this->once())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $this->storeMock
            ->expects($this->once())
            ->method('getId')
            ->willReturn($storeId);

        $this->config->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);


        $this->validator->validateInput($this->contextMock);
    }

    public function testCartIdMissing()
    {
        $storeId = 1;
        $exceptionMessage = 'Required parameter "cart_id" is missing.';
        $this->expectException('Magento\Framework\Exception\InvalidArgumentException');
        $this->expectExceptionMessage($exceptionMessage);

        $this->contextMock
            ->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($this->contextExtensionMock);

        $this->contextExtensionMock
            ->expects($this->once())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $this->storeMock
            ->expects($this->once())
            ->method('getId')
            ->willReturn($storeId);

        $this->config->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);


        $this->validator->validateInput(
            $this->contextMock,
            ['input' => []]
        );
    }
}
