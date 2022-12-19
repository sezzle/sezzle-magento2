<?php

namespace Sezzle\Sezzlepay\Gateway\Http;

use Exception;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Sezzle\Sezzlepay\Gateway\Config\Config as SezzleConfig;
use Magento\Store\Model\StoreManagerInterface;

/**
 * TransferFactory
 */
class TransferFactory implements TransferFactoryInterface
{
    /**
     * @var string[] Allowed route parameters
     */
    private static $routeParams = ['order_uuid', 'customer_uuid'];

    /**
     * @var TransferBuilder
     */
    private $transferBuilder;

    /**
     * @var SezzleConfig
     */
    private $config;

    /**
     * @var string
     */
    private $method;

    /**
     * @var string
     */
    private $uriPath;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var AuthenticationService
     */
    private $authenticationService;

    /**
     * TransferFactory constructor
     * @param TransferBuilder $transferBuilder
     * @param SezzleConfig $sezzleConfig
     * @param StoreManagerInterface $storeManager
     * @param AuthenticationService $authenticationService
     * @param string|null $method
     * @param string|null $uriPath
     */
    public function __construct(
        TransferBuilder       $transferBuilder,
        SezzleConfig          $sezzleConfig,
        StoreManagerInterface $storeManager,
        AuthenticationService $authenticationService,
        string                $method = null,
        string                $uriPath = null
    )
    {
        $this->transferBuilder = $transferBuilder;
        $this->config = $sezzleConfig;
        $this->storeManager = $storeManager;
        $this->authenticationService = $authenticationService;
        $this->method = $method;
        $this->uriPath = $uriPath;
    }

    /**
     * Builds gateway transfer object
     *
     * @inheritDoc
     * @throws NoSuchEntityException|InputException
     * @throws ClientException
     */
    public function create(array $request): TransferInterface
    {
        try {
            $storeId = isset($request['__store_id']) ?
                (int)$request['__store_id'] : $this->storeManager->getStore()->getId();
            $token = $this->authenticationService->getToken($storeId);
        } catch (Exception $e) {
            throw new ClientException(__($e->getMessage()));
        }

        $method = $request['__method'] ?? $this->method;

        $args = $this->removeAndReturnArgs($request);
        $uri = $request['__uri'] ?? $this->getURI($args, $storeId);
        unset($request['__uri']);

        return $this->transferBuilder
            ->setMethod($method)
            ->setHeaders(
                [
                    'Content-Type' => Client::CONTENT_TYPE_JSON,
                    'Authorization' => 'Bearer ' . $token
                ])
            ->setBody($request)
            ->setUri($uri)
            ->build();
    }

    /**
     * Get API URL
     *
     * @throws NoSuchEntityException
     * @throws InputException
     */
    private function getURI(array $args, int $storeId): string
    {
        foreach ($args as $argKey => $argVal) {
            $this->uriPath = str_replace('{' . $argKey . '}', (string)$argVal, $this->uriPath);
        }

        return $this->config->getGatewayURL($storeId) . $this->uriPath;
    }

    /**
     * Unset route params and build arguments for endpoint building
     *
     * @param array $request
     * @return array
     */
    private function removeAndReturnArgs(array &$request): array
    {
        $argsToReturn = [];
        foreach (self::$routeParams as $arg) {
            if (isset($request['__route_params'][$arg])) {
                $argsToReturn[$arg] = $request['__route_params'][$arg];
            }
        }

        unset($request['__route_params'], $request['__method'], $request['__store_id']);
        return $argsToReturn;
    }
}
