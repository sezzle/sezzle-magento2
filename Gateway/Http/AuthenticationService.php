<?php

namespace Sezzle\Sezzlepay\Gateway\Http;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Validation\ValidationException;
use Psr\Log\LoggerInterface;
use Sezzle\Sezzlepay\Gateway\Config\Config;
use Magento\Framework\HTTP\Client\Curl;
use Sezzle\Sezzlepay\Helper\Data;

/**
 * AuthenticationService
 */
class AuthenticationService
{
    const TOKEN_CACHE_PREFIX = 'SEZZLE_AUTH_TOKEN';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var LoggerInterface
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
     * @var Data
     */
    private $helper;

    /**
     * AuthenticationService constructor.
     * @param Config $config
     * @param CacheInterface $cache
     * @param LoggerInterface $logger
     * @param Data $helper
     * @param Json $jsonSerializer
     * @param Curl $curl
     */
    public function __construct(
        Config          $config,
        CacheInterface  $cache,
        LoggerInterface $logger,
        Data            $helper,
        Json            $jsonSerializer,
        Curl            $curl
    )
    {
        $this->config = $config;
        $this->cache = $cache;
        $this->logger = $logger;
        $this->helper = $helper;
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
        $data = [
            'public_key' => $this->config->getPublicKey($storeId),
            'private_key' => $this->config->getPrivateKey($storeId)
        ];

        try {
            $this->curl->setTimeout(Client::TIMEOUT);
            $this->curl->setHeaders(
                [
                    'Content-Type' => Client::CONTENT_TYPE_JSON,
                    'Sezzle-Platform' => $this->helper->getEncodedPlatformDetails()
                ]
            );

            $url = $this->config->getGatewayURL($storeId) . '/authentication';

            $log = [
                'log_origin' => __METHOD__,
                'request' => [
                    'uri' => $url,
                    'body' => $data
                ]
            ];

            $this->curl->post($url, $this->jsonSerializer->serialize($data));

            $responseJSON = $this->curl->getBody();
            $response = $this->jsonSerializer->unserialize($responseJSON);
            $log['response']['body'] = $response;

            if (!isset($response['token'])) {
                throw new LocalizedException(__('Auth token unavailable.'));
            }

            return $response['token'];
        } catch (InputException|NoSuchEntityException|LocalizedException $e) {
            $this->logger->critical($e->getMessage());
            $log['error'] = $e->getMessage();

            throw new LocalizedException(__($e->getMessage()));
        } finally {
            $this->helper->logSezzleActions($log);
        }
    }

    /**
     * Validate API Keys
     *
     * @param string $publicKey
     * @param string $privateKey
     * @param string $paymentMode
     * @return string
     * @throws ValidationException
     */
    public function validateAPIKeys(string $publicKey, string $privateKey, string $paymentMode): string
    {
        $data = [
            'public_key' => $publicKey,
            'private_key' => $privateKey
        ];

        try {
            $this->curl->setTimeout(Client::TIMEOUT);
            $this->curl->addHeader('Content-Type', Client::CONTENT_TYPE_JSON);

            $replaceValue = $paymentMode === Config::PAYMENT_MODE_SANDBOX ? Config::PAYMENT_MODE_SANDBOX . '.' : '';
            $url = sprintf(Config::GATEWAY_URL, $replaceValue, Config::API_VERSION_V2) . '/authentication';

            $log = [
                'log_origin' => __METHOD__,
                'request' => [
                    'uri' => $url,
                    'body' => $data
                ]
            ];

            $this->curl->post($url, $this->jsonSerializer->serialize($data));

            $responseJSON = $this->curl->getBody();
            $response = $this->jsonSerializer->unserialize($responseJSON);
            $log['response']['body'] = $response;

            if (!isset($response['token']) || !$response['token']) {
                throw new LocalizedException(__('Auth token unavailable.'));
            }

            return (bool)$response['token'];
        } catch (InputException|NoSuchEntityException|LocalizedException $e) {
            $this->logger->critical($e->getMessage());
            $log['error'] = $e->getMessage();

            throw new ValidationException(__($e->getMessage()));
        } finally {
            $this->helper->logSezzleActions($log);
        }
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
     * @return void
     */
    private function saveCacheToken(int $websiteId, string $token, int $lifetime): void
    {
        $this->cache->save($token, self::TOKEN_CACHE_PREFIX . $websiteId, [], $lifetime);
    }
}
