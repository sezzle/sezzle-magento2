<?php

namespace Sezzle\Sezzlepay\Gateway\Http;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
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
    private string $uriPath;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * TransferFactory constructor
     * @param TransferBuilder $transferBuilder
     * @param SezzleConfig $sezzleConfig
     * @param StoreManagerInterface $storeManager
     * @param string|null $method
     * @param string|null $uriPath
     */
    public function __construct(
        TransferBuilder       $transferBuilder,
        SezzleConfig          $sezzleConfig,
        StoreManagerInterface $storeManager,
        string                $method = null,
        string                $uriPath = null
    )
    {
        $this->transferBuilder = $transferBuilder;
        $this->config = $sezzleConfig;
        $this->storeManager = $storeManager;
        $this->method = $method;
        $this->uriPath = $uriPath;
    }

    /**
     * Builds gateway transfer object
     *
     * @inerhitDoc
     * @throws NoSuchEntityException|InputException
     */
    public function create(array $request): TransferInterface
    {
        $storeId = (int)$request['__storeId'] ?? $this->storeManager->getStore()->getId();
        unset($request['__storeId']);

        $args = $this->removeAndReturnArgs($request);
        return $this->transferBuilder
            ->setClientConfig(['__storeId' => $storeId])
            ->setMethod($this->method)
            ->setHeaders(['Content-Type' => 'application/json'])
            ->setBody($request)
            ->setUri($this->getAPIUrl($args, $storeId))
            ->build();
    }

    /**
     * Get API URL
     *
     * @throws NoSuchEntityException
     * @throws InputException
     */
    private function getAPIUrl(array $args, int $storeId): string
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
            if (isset($request['route_params'][$arg])) {
                $argsToReturn[$arg] = $request['route_params'][$arg];
            }
        }
        unset($request['route_params']);
        return $argsToReturn;
    }
}
