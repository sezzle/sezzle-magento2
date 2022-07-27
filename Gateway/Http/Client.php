<?php

namespace Sezzle\Sezzlepay\Gateway\Http;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\HTTP\Client\Curl;
use Sezzle\Sezzlepay\Helper\Data;
use Sezzle\Sezzlepay\Model\Api\ApiParamsInterface;

/**
 * Client
 */
class Client implements ClientInterface
{

    const HTTP_GET = 'GET';
    const HTTP_POST = 'POST';
    const HTTP_PUT = 'PUT';
    const HTTP_PATCH = 'PATCH';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @var AuthTokenService
     */
    private $authTokenService;

    /**
     * @var Curl
     */
    private $curl;

    /**
     * @var Data
     */
    private $helper;

    /**
     * Client constructor.
     *
     * @param AuthTokenService $authTokenService
     * @param LoggerInterface $logger
     * @param Json $jsonSerializer
     * @param Curl $curl
     * @param Data $helper
     */
    public function __construct(
        AuthTokenService $authTokenService,
        LoggerInterface  $logger,
        Json             $jsonSerializer,
        Curl             $curl,
        Data             $helper
    )
    {
        $this->authTokenService = $authTokenService;
        $this->logger = $logger;
        $this->jsonSerializer = $jsonSerializer;
        $this->curl = $curl;
        $this->helper = $helper;
    }

    /**
     * @inheritDoc
     * @throws ClientException
     */
    public function placeRequest(TransferInterface $transferObject): array
    {
        $log = [
            'log_origin' => __METHOD__,
            'request' => [
                'uri' => $transferObject->getUri(),
                'body' => $transferObject->getBody()
            ],
        ];
        $clientConfig = $transferObject->getClientConfig();
        $storeId = $clientConfig['__storeId'];
        unset($clientConfig['__storeId']);

        try {
            $this->curl->setTimeout(ApiParamsInterface::TIMEOUT);

            $this->curl->setHeaders([
                'Content-Type' => ApiParamsInterface::CONTENT_TYPE_JSON,
                'Authorization' => 'Bearer ' . $this->authTokenService->getToken($storeId)
            ]);

            switch ($transferObject->getMethod()) {
                case self::HTTP_POST:
                    $this->curl->post($transferObject->getUri(), $this->jsonSerializer->serialize($transferObject->getBody()));
                    break;
                case self::HTTP_GET:
                    $this->curl->get($transferObject->getUri());
                    break;
            }

            $responseJSON = $this->curl->getBody();
            $log['response'] = [
                'status' => $this->curl->getStatus(),
                'body' => $responseJSON
            ];

            return $this->jsonSerializer->unserialize($responseJSON);
        } catch (LocalizedException $e) {
            $this->logger->critical($e->getMessage());
            $log['error'] = $e->getMessage();

            throw new ClientException(
                __('Something went wrong in the payment gateway.')
            );
        } finally {
            $this->helper->logSezzleActions($log);
        }
    }
}
