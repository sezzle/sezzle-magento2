<?php

namespace Sezzle\Sezzlepay\Gateway\Http;

use InvalidArgumentException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\HTTP\Client\Curl;
use Sezzle\Sezzlepay\Helper\Data;

/**
 * Client
 */
class Client implements ClientInterface
{

    const HTTP_GET = 'GET';
    const HTTP_POST = 'POST';
    const HTTP_PUT = 'PUT';
    const HTTP_PATCH = 'PATCH';

    const TIMEOUT = 80;
    const CONTENT_TYPE_JSON = "application/json";


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
     * Client constructor.
     *
     * @param Json $jsonSerializer
     * @param Curl $curl
     * @param Data $helper
     */
    public function __construct(
        Json $jsonSerializer,
        Curl $curl,
        Data $helper
    )
    {
        $this->jsonSerializer = $jsonSerializer;
        $this->curl = $curl;
        $this->helper = $helper;
    }

    /**
     * @inheritDoc
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $log = [
            'log_origin' => __METHOD__,
            'request' => [
                'uri' => $transferObject->getUri(),
                'body' => $transferObject->getBody()
            ],
        ];


        $this->curl->setTimeout(self::TIMEOUT);

        $this->curl->setHeaders($transferObject->getHeaders());

        switch ($transferObject->getMethod()) {
            case self::HTTP_POST:
                $this->curl->post($transferObject->getUri(), $this->jsonSerializer->serialize($transferObject->getBody()));
                break;
            case self::HTTP_GET:
                $this->curl->get($transferObject->getUri());
                break;
        }

        $response = $this->curl->getBody();
        $log['response'] = [
            'status' => $this->curl->getStatus(),
            'body' => $response
        ];

        $this->helper->logSezzleActions($log);

        try {
            return $this->jsonSerializer->unserialize($response);
        } catch (InvalidArgumentException $e) {
            return $response; // settlement details endpoint return CSV, so it will fail in above JSON unserializer
        }
    }
}
