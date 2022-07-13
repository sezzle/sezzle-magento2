<?php

namespace Sezzle\Sezzlepay\Gateway\Http;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger as PaymentLogger;
use Magento\Framework\HTTP\ZendClientFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\HTTP\Client\Curl;
use Sezzle\Sezzlepay\Model\Api\ApiParamsInterface;

/**
 * Client
 */
class Client implements ClientInterface
{

    const HTTP_GET = 'GET';
    const HTTP_POST = 'POST';
    const HTTP_PUT = 'PUT';
    const HTTP_PATCH = "PATCH";

    /**
     * @var ZendClientFactory
     */
    private $httpClientFactory;

    /**
     * @var PaymentLogger
     */
    private $paymentLogger;

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
     * Client constructor.
     *
     * @param PaymentLogger $paymentLogger
     * @param AuthTokenService $authTokenService
     * @param LoggerInterface $logger
     * @param Json $jsonSerializer
     * @param Curl $curl
     */
    public function __construct(
        PaymentLogger    $paymentLogger,
        AuthTokenService $authTokenService,
        LoggerInterface  $logger,
        Json   $jsonSerializer,
        Curl             $curl
    )
    {
        $this->paymentLogger = $paymentLogger;
        $this->authTokenService = $authTokenService;
        $this->logger = $logger;
        $this->jsonSerializer = $jsonSerializer;
        $this->curl = $curl;
    }

    /**
     * @param TransferInterface $transferObject
     * @return array
     * @throws ClientException
     */
    public function placeRequest(TransferInterface $transferObject): array
    {
//        $log = [
//            'request' => [
//                'uri' => $transferObject->getUri()
//            ],
//        ];

        $clientConfig = $transferObject->getClientConfig();
        $storeId = $clientConfig['__storeId'];
        unset($clientConfig['__storeId']);

        $this->curl->setTimeout(ApiParamsInterface::TIMEOUT);
        $this->curl->addHeader("Content-Type", ApiParamsInterface::CONTENT_TYPE_JSON);

        $this->curl->setHeaders([
            'Content-Type' => ApiParamsInterface::CONTENT_TYPE_JSON,
            'Authorization' => 'Bearer ' . $this->authTokenService->getToken($storeId)
        ]);

        switch ($transferObject->getMethod()) {
            case 'POST':
                $this->curl->post($transferObject->getUri(), $this->jsonSerializer->serialize($transferObject->getBody()));
                break;
            case 'get':
                $this->curl->get($transferObject->getUri());
                break;
        }


        $responseJSON = $this->curl->getBody();

        return $this->jsonSerializer->unserialize($responseJSON);

//        try {
//            //            $client->setConfig(['maxredirects' => 0, 'timeout' => 10]);
//            $client->setUri($transferObject->getUri())
//                ->setMethod($transferObject->getMethod())
//                ->setHeaders([
//                    'Content-Type: application/json',
//                    'Authorization: Bearer ' . $this->authTokenService->getToken($storeId)
//                ]);
//
//            $request = $transferObject->getBody();
//            if (!empty($request)) {
//                $client->setRawData($this->json->serialize($request), 'application/json');
////                $log['request']['body'] = $request;
//            }
//
//            $response = $client->request();
////            $log['response'] = $response->getBody();
//
//            if (!$response->isSuccessful()) {
//                throw new ClientException(
//                    __($response->getBody())
//                );
//            }
//
//            return $this->json->unserialize($response->getBody());
//        } catch (Exception $e) {
//            $this->logger->critical($e);
////            $log['error'] = $e->getMessage();
//
//            throw new ClientException(
//                __('Something went wrong in the payment gateway.')
//            );
//        }
////        finally {
////            $log['log_origin'] = __METHOD__;
////            $this->paymentLogger->debug($log);
////        }
    }
}
