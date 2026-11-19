<?php

namespace Sezzle\Sezzlepay\Test\Unit\Block\Adminhtml\System\Config\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sezzle\Sezzlepay\Block\Adminhtml\System\Config\Field\SezzleAds;
use Sezzle\Sezzlepay\Model\SezzleAds\Enrollment;

/**
 * @covers \Sezzle\Sezzlepay\Block\Adminhtml\System\Config\Field\SezzleAds
 */
class SezzleAdsTest extends TestCase
{
    private const PATH = 'payment/sezzlepay/merchant_id';
    private const MERCHANT_UUID = 'bea82572-6387-4b7f-81c9-4b92854316c6';

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfig;

    /**
     * @var Enrollment|MockObject
     */
    private $enrollment;

    /**
     * @var SezzleAds
     */
    private $block;

    protected function setUp(): void
    {
        $this->request = $this->createMock(RequestInterface::class);
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->enrollment = $this->createMock(Enrollment::class);

        // The block's parents fetch these from the global object manager.
        (new ObjectManager($this))->prepareObjectManager([
            [JsonHelper::class, $this->createMock(JsonHelper::class)],
            [DirectoryHelper::class, $this->createMock(DirectoryHelper::class)],
            [SecureHtmlRenderer::class, $this->createMock(SecureHtmlRenderer::class)],
        ]);

        $context = $this->createMock(Context::class);
        $context->method('getRequest')->willReturn($this->request);
        $context->method('getScopeConfig')->willReturn($this->scopeConfig);

        $this->block = new SezzleAds($context, $this->enrollment);
    }

    public function testRendersNothingWhenNotEnrolled(): void
    {
        $this->givenParams([]);
        $this->scopeConfig->method('getValue')
            ->with(self::PATH, ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
            ->willReturn(self::MERCHANT_UUID);
        $this->enrollment->expects($this->once())
            ->method('isEnrolled')
            ->with(self::MERCHANT_UUID)
            ->willReturn(false);

        $this->assertSame('', $this->block->render($this->createMock(AbstractElement::class)));
    }

    public function testRendersTheFieldWhenEnrolled(): void
    {
        $this->givenParams([]);
        $this->scopeConfig->method('getValue')->willReturn(self::MERCHANT_UUID);
        $this->enrollment->method('isEnrolled')->willReturn(true);

        $element = $this->createMock(AbstractElement::class);
        $element->method('getHtmlId')->willReturn('payment_us_sezzlepay_widget_sezzle_ads');

        $this->assertStringContainsString(
            'row_payment_us_sezzlepay_widget_sezzle_ads',
            $this->block->render($element)
        );
    }

    #[DataProvider('scopeProvider')]
    public function testReadsTheMerchantUUIDAtTheScopeBeingEdited(array $params, string $scope, $scopeCode): void
    {
        $this->givenParams($params);
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(self::PATH, $scope, $scopeCode)
            ->willReturn(self::MERCHANT_UUID);
        $this->enrollment->expects($this->once())
            ->method('isEnrolled')
            ->with(self::MERCHANT_UUID)
            ->willReturn(false);

        $this->block->render($this->createMock(AbstractElement::class));
    }

    public static function scopeProvider(): array
    {
        return [
            'store' => [['store' => '3', 'website' => '2'], ScopeInterface::SCOPE_STORE, '3'],
            'website' => [['website' => '2'], ScopeInterface::SCOPE_WEBSITE, '2'],
            'default' => [[], ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null],
        ];
    }

    public function testAsksOncePerMerchantAcrossFields(): void
    {
        $this->givenParams([]);
        $this->scopeConfig->method('getValue')->willReturn(self::MERCHANT_UUID);
        $this->enrollment->expects($this->once())->method('isEnrolled')->willReturn(false);

        foreach (range(1, 4) as $ignored) {
            $this->block->render($this->createMock(AbstractElement::class));
        }
    }

    private function givenParams(array $params): void
    {
        $this->request->method('getParam')->willReturnCallback(
            static function (string $key) use ($params) {
                return $params[$key] ?? null;
            }
        );
    }
}
