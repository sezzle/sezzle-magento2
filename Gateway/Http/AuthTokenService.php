<?php

namespace Sezzle\Sezzlepay\Gateway\Http;

use Magento\Payment\Model\Method\Logger;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Sezzle\Sezzlepay\Gateway\Config\Config;
use Magento\Framework\HTTP\Client\Curl;
use Sezzle\Sezzlepay\Model\Api\ApiParamsInterface;

/**
 * AuthTokenService
 */
class AuthTokenService
{
    const TOKEN_CACHE_PREFIX = 'SEZZLE_AUTH_TOKEN';

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @var Curl
     */
    private $curl;

    /**
     * AuthTokenService constructor.
     * @param StoreManagerInterface $storeManager
     * @param Config $config
     * @param CacheInterface $cache
     * @param Logger $logger
     * @param Json $jsonSerializer
     * @param Curl $curl
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Config                $config,
        CacheInterface        $cache,
        Logger                $logger,
        Json                  $jsonSerializer,
        Curl                  $curl
    )
    {
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->cache = $cache;
        $this->logger = $logger;
        $this->jsonSerializer = $jsonSerializer;
        $this->curl = $curl;
    }

    /**
     * Get auth token
     *
     * @param int|null $storeId
     * @return string
     * @throws LocalizedException
     */
    public function getToken(int $storeId = null): string
    {
        $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();

        if ($token = $this->loadCacheToken($websiteId)) {
            return $token;
        }

        $data = [
            'public_key' => $this->config->getPublicKey($storeId),
            'private_key' => $this->config->getPrivateKey($storeId)
        ];

        $this->curl->setTimeout(ApiParamsInterface::TIMEOUT);
        $this->curl->addHeader("Content-Type", ApiParamsInterface::CONTENT_TYPE_JSON);

        $url = $this->config->getGatewayURL($storeId) . 'v2/authentication';
        $this->curl->post($url, $this->jsonSerializer->serialize($data));

        $responseJSON = $this->curl->getBody();
        $response = $this->jsonSerializer->unserialize($responseJSON);

        if (!isset($response['token'])) {
            throw new LocalizedException(__('Auth token unavailable.'));
        }

        return $response['token'];
    }

    /**
     * Get token from cache
     *
     * @param int $websiteId
     * @return string
     */
    private function loadCacheToken(int $websiteId): string
    {
        return $this->cache->load(self::TOKEN_CACHE_PREFIX . $websiteId);
    }

    /**
     * Cache token
     *
     * @param int $websiteId
     * @param string $token
     * @param int $lifetime
     */
    private function saveCacheToken(int $websiteId, string $token, int $lifetime)
    {
        $this->cache->save($token, self::TOKEN_CACHE_PREFIX . $websiteId, [], $lifetime);
    }
}
