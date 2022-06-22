<?php

namespace Sezzle\Sezzlepay\Gateway\Http;

use Exception;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Payment\Model\Method\Logger;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Sezzle\Sezzlepay\Gateway\Config\Config;

/**
 * AuthTokenService
 */
class AuthTokenService
{
    const TOKEN_CACHE_PREFIX = 'SEZZLE_AUTH_TOKEN';

    /**
     * @var ZendClientFactory
     */
    private $clientFactory;

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
     * AuthTokenService constructor.
     * @param ZendClientFactory $clientFactory
     * @param StoreManagerInterface $storeManager
     * @param Config $config
     * @param CacheInterface $cache
     * @param Logger $logger
     * @param Json $jsonSerializer
     */
    public function __construct(
        ZendClientFactory     $clientFactory,
        StoreManagerInterface $storeManager,
        Config                $config,
        CacheInterface        $cache,
        Logger                $logger,
        Json                  $jsonSerializer
    )
    {
        $this->clientFactory = $clientFactory;
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->cache = $cache;
        $this->logger = $logger;
        $this->jsonSerializer = $jsonSerializer;
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

//        $log = [
//            'request' => $data
//        ];

        $client = $this->clientFactory->create();

        try {
            $client
                ->setConfig(['timeout' => 5])
                ->setUri($this->config->getGatewayURL($storeId))
                ->setMethod(Client::HTTP_POST)
                ->setHeaders('Content-type', 'application/json')
                ->setRawData($this->jsonSerializer->serialize($data));

            $response = $client->request();
//            $log['response'] = $response->getBody();

            if ($response->getStatus() != 201 || empty($response->getBody())) {
                throw new LocalizedException(__('Auth token unavailable.'));
            }

            $result = $response->getBody();
            $result = $this->jsonSerializer->unserialize($result);

            if (!isset($result['token'])) {
                throw new LocalizedException(__('Auth token unavailable.'));
            }

            // TODO: Need to figure out later on
//            if (isset($result['expires_in']) && $result['expires_in'] > 300) {
//                $this->saveCacheToken($websiteId, $token, $result['expires_in'] - 300);
//            }

            return $result['token'];
        } catch (LocalizedException $e) {
//            $log['error'] = $e->getMessage();
            throw $e;
        } catch (Exception $e) {
//            $log['error'] = $e->getMessage();
            throw new LocalizedException(__($e->getMessage()));
        }
//        finally {
//            $log['log_origin'] = __METHOD__;
//            $this->logger->debug($log);
//        }
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
