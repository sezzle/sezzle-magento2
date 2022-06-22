<?php

namespace Sezzle\Sezzlepay\Gateway\Http\Client;

use Exception;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger as PaymentLogger;
use Magento\Framework\HTTP\ZendClientFactory;
use Psr\Log\LoggerInterface;

/**
 * Client
 */
class Client implements ClientInterface
{

    const HTTP_GET = "get";
    const HTTP_POST = "post";
    const HTTP_PUT = "put";
    const HTTP_PATCH = "patch";

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
    private $json;

    /**
     * Client constructor.
     *
     * @param ZendClientFactory $httpClientFactory
     * @param PaymentLogger $paymentLogger
     * @param LoggerInterface $logger
     * @param Json $json
     */
    public function __construct(
        ZendClientFactory $httpClientFactory,
        PaymentLogger     $paymentLogger,
        LoggerInterface   $logger,
        Json              $json
    )
    {
        $this->httpClientFactory = $httpClientFactory;
        $this->paymentLogger = $paymentLogger;
        $this->logger = $logger;
        $this->json = $json;
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
        $client = $this->httpClientFactory->create();

        $clientConfig = $transferObject->getClientConfig();
        $storeId = $clientConfig['__store'] ?? null;
        unset($clientConfig['__store']);

        try {
            //            $client->setConfig(['maxredirects' => 0, 'timeout' => 10]);
            $client->setUri($transferObject->getUri())
                ->setMethod($transferObject->getMethod())
                ->setHeaders([
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->tokenService->getToken($storeId) // TODO: Need to add a token service
                ]);

            $request = $transferObject->getBody();
            if (!empty($request)) {
                $client->setRawData($this->json->serialize($request), 'application/json');
//                $log['request']['body'] = $request;
            }

            $response = $client->request();
//            $log['response'] = $response->getBody();

            if (!$response->isSuccessful()) {
                throw new ClientException(
                    __($response->getBody())
                );
            }

            return $this->json->unserialize($response->getBody());
        } catch (Exception $e) {
            $this->logger->critical($e);
//            $log['error'] = $e->getMessage();

            throw new ClientException(
                __('Something went wrong in the payment gateway.')
            );
        }
//        finally {
//            $log['log_origin'] = __METHOD__;
//            $this->paymentLogger->debug($log);
//        }
    }
}
