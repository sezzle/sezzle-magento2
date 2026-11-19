<?php

namespace Sezzle\Sezzlepay\Test\Unit\Model\SezzleAds;

use Exception;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\HTTP\Client\CurlFactory;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sezzle\Sezzlepay\Gateway\Config\Config;
use Sezzle\Sezzlepay\Helper\Data;
use Sezzle\Sezzlepay\Model\SezzleAds\Enrollment;

/**
 * @covers \Sezzle\Sezzlepay\Model\SezzleAds\Enrollment
 */
class EnrollmentTest extends TestCase
{
    private const MERCHANT_UUID = 'BEA82572-6387-4b7f-81c9-4b92854316c6';
    private const CACHE_KEY = 'sezzle_ads_enrollment_bea8257263874b7f81c94b92854316c6';

    /**
     * @var CacheInterface|MockObject
     */
    private $cache;

    /**
     * @var CurlFactory|MockObject
     */
    private $curlFactory;

    /**
     * @var Curl|MockObject
     */
    private $curl;

    /**
     * @var Config|MockObject
     */
    private $config;

    /**
     * @var Enrollment
     */
    private $enrollment;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheInterface::class);
        $this->curl = $this->createMock(Curl::class);
        $this->curlFactory = $this->createMock(CurlFactory::class);
        $this->curlFactory->method('create')->willReturn($this->curl);
        $this->config = $this->createMock(Config::class);
        $this->config->method('getWidgetURL')
            ->with(Config::API_VERSION_V1)
            ->willReturn('https://widget.sezzle.com/v1');

        $this->enrollment = new Enrollment(
            $this->cache,
            $this->curlFactory,
            new Json(),
            $this->config,
            $this->createMock(Data::class)
        );
    }

    #[DataProvider('noMerchantUUIDProvider')]
    public function testNoRequestWithoutAMerchantUUID(?string $merchantUUID): void
    {
        $this->curlFactory->expects($this->never())->method('create');
        $this->cache->expects($this->never())->method('load');
        $this->cache->expects($this->never())->method('save');

        $this->assertFalse($this->enrollment->isEnrolled($merchantUUID));
    }

    public static function noMerchantUUIDProvider(): array
    {
        return [
            'null' => [null],
            'empty' => [''],
            'blank' => ['   '],
            'not a uuid' => ['merchant-123'],
        ];
    }

    #[DataProvider('cachedProvider')]
    public function testUsesTheCachedAnswer(string $cached, bool $expected): void
    {
        $this->cache->expects($this->once())->method('load')->with(self::CACHE_KEY)->willReturn($cached);
        $this->curlFactory->expects($this->never())->method('create');
        $this->cache->expects($this->never())->method('save');

        $this->assertSame($expected, $this->enrollment->isEnrolled(self::MERCHANT_UUID));
    }

    public static function cachedProvider(): array
    {
        return [
            'enrolled' => ['1', true],
            'not enrolled' => ['0', false],
        ];
    }

    #[DataProvider('responseProvider')]
    public function testAsksProductionWidgetServerAndCachesTheAnswer(
        int    $status,
        string $body,
        bool   $expected,
        string $cachedValue,
        int    $lifetime
    ): void {
        $this->cache->method('load')->willReturn(false);
        $this->curl->expects($this->once())->method('setTimeout')->with(Enrollment::TIMEOUT);
        $this->curl->expects($this->once())
            ->method('get')
            ->with('https://widget.sezzle.com/v1/ads/enrollment?merchant_uuid=' . self::MERCHANT_UUID);
        $this->curl->method('getStatus')->willReturn($status);
        $this->curl->method('getBody')->willReturn($body);
        $this->cache->expects($this->once())
            ->method('save')
            ->with($cachedValue, self::CACHE_KEY, [], $lifetime);

        $this->assertSame($expected, $this->enrollment->isEnrolled(self::MERCHANT_UUID));
    }

    public static function responseProvider(): array
    {
        return [
            'enrolled' => [200, '{"enabled":true}', true, '1', Enrollment::CACHE_LIFETIME],
            'not enrolled' => [200, '{"enabled":false}', false, '0', Enrollment::CACHE_LIFETIME],
            'truthy but not true' => [200, '{"enabled":"true"}', false, '0', Enrollment::CACHE_LIFETIME],
            'no enabled field' => [200, '{}', false, '0', Enrollment::ERROR_CACHE_LIFETIME],
            'bad request' => [400, '{"error":"invalid merchant_uuid"}', false, '0', Enrollment::ERROR_CACHE_LIFETIME],
            'server error' => [500, '', false, '0', Enrollment::ERROR_CACHE_LIFETIME],
            'not json' => [200, '<html></html>', false, '0', Enrollment::ERROR_CACHE_LIFETIME],
        ];
    }

    public function testARequestFailureIsNotEnrolledAndRetriedSooner(): void
    {
        $this->cache->method('load')->willReturn(false);
        $this->curl->method('get')->willThrowException(new Exception('Operation timed out'));
        $this->cache->expects($this->once())
            ->method('save')
            ->with('0', self::CACHE_KEY, [], Enrollment::ERROR_CACHE_LIFETIME);

        $this->assertFalse($this->enrollment->isEnrolled(self::MERCHANT_UUID));
    }
}
