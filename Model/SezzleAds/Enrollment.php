<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model\SezzleAds;

use Exception;
use Magento\Framework\App\Cache\Type\Config as ConfigCache;
use Magento\Framework\HTTP\Client\CurlFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Sezzle\Sezzlepay\Gateway\Config\Config;
use Sezzle\Sezzlepay\Helper\Data;

/**
 * Whether Sezzle has enrolled a merchant in Sezzle Ads.
 *
 * Asks widget-server, which is the only place enrollment is read from outside Sezzle. Always the production
 * widget-server, whatever the payment mode: the SDK on the success page always asks production whether to show the
 * ad, so the settings page has to agree with it. The endpoint is public, so no API keys are sent.
 *
 * Only the admin settings page calls this. The storefront never does: the SDK and widget-server already keep the ad
 * off a store that is not enrolled.
 */
class Enrollment
{
    const ENDPOINT_PATH = '/ads/enrollment';
    const TIMEOUT = 3;
    const CACHE_PREFIX = 'sezzle_ads_enrollment_';
    /** An answer from widget-server, kept long enough to cover a merchant lingering on the settings page. */
    const CACHE_LIFETIME = 300;
    /** No answer (error, timeout, non-200), retried sooner so a blip does not hide the settings for long. */
    const ERROR_CACHE_LIFETIME = 60;
    const UUID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    /**
     * Configuration cache, so refreshing the Configuration cache type also drops a stale enrollment answer
     *
     * @var ConfigCache
     */
    private $cache;

    /**
     * @var CurlFactory
     */
    private $curlFactory;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @param ConfigCache $cache
     * @param CurlFactory $curlFactory
     * @param Json $jsonSerializer
     * @param Config $config
     * @param Data $helper
     */
    public function __construct(
        ConfigCache    $cache,
        CurlFactory    $curlFactory,
        Json           $jsonSerializer,
        Config         $config,
        Data           $helper
    )
    {
        $this->cache = $cache;
        $this->curlFactory = $curlFactory;
        $this->jsonSerializer = $jsonSerializer;
        $this->config = $config;
        $this->helper = $helper;
    }

    /**
     * Whether the merchant is enrolled. False, without a request, when there is no merchant UUID.
     *
     * @param string|null $merchantUUID
     * @return bool
     */
    public function isEnrolled(?string $merchantUUID): bool
    {
        $merchantUUID = trim((string)$merchantUUID);
        if (!preg_match(self::UUID_PATTERN, $merchantUUID)) {
            return false;
        }

        // Cache ids allow only letters, digits and underscores.
        $cacheKey = self::CACHE_PREFIX . str_replace('-', '', strtolower($merchantUUID));
        $cached = $this->cache->load($cacheKey);
        if ($cached === '1' || $cached === '0') {
            return $cached === '1';
        }

        $enrolled = $this->fetch($merchantUUID);
        $this->cache->save(
            $enrolled === true ? '1' : '0',
            $cacheKey,
            [ConfigCache::CACHE_TAG],
            $enrolled === null ? self::ERROR_CACHE_LIFETIME : self::CACHE_LIFETIME
        );

        return $enrolled === true;
    }

    /**
     * Asks widget-server. Null when there is no usable answer, which the caller treats as not enrolled.
     *
     * @param string $merchantUUID
     * @return bool|null
     */
    private function fetch(string $merchantUUID): ?bool
    {
        $url = $this->config->getWidgetURL(Config::API_VERSION_V1) . self::ENDPOINT_PATH
            . '?merchant_uuid=' . rawurlencode($merchantUUID);
        $log = [
            'log_origin' => __METHOD__,
            'request' => ['uri' => $url],
        ];

        try {
            $curl = $this->curlFactory->create();
            $curl->setTimeout(self::TIMEOUT);
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, self::TIMEOUT);
            $curl->get($url);

            $status = $curl->getStatus();
            $log['response']['status'] = $status;
            if ($status !== 200) {
                return null;
            }

            $response = $this->jsonSerializer->unserialize($curl->getBody());
            $log['response']['body'] = $response;
            if (!is_array($response) || !array_key_exists('enabled', $response)) {
                return null;
            }

            return $response['enabled'] === true;
        } catch (Exception $e) {
            $log['error'] = $e->getMessage();
            return null;
        } finally {
            $this->helper->logSezzleActions($log);
        }
    }
}
