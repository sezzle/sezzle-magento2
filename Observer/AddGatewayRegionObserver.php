<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Observer;

use Magento\Framework\App\Config\ValueFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface as StoreScopeInterface;
use Sezzle\Sezzlepay\Model\Sezzle;
use Sezzle\Sezzlepay\Model\System\Config\Container\SezzleIdentity;
use Sezzle\Sezzlepay\Model\System\Config\Source\Payment\GatewayRegion;

/**
 * Class AddGatewayRegionObserver
 * @package Sezzle\Sezzlepay\Observer
 */
class AddGatewayRegionObserver implements ObserverInterface
{

    /**
     * @var ValueFactory
     */
    private $configValueFactory;
    /**
     * @var GatewayRegion
     */
    private $gatewayRegion;

    /**
     * AddGatewayRegionObserver constructor.
     * @param ValueFactory $configValueFactory
     */
    public function __construct(
        GatewayRegion $gatewayRegion,
        ValueFactory $configValueFactory
    ) {
        $this->gatewayRegion = $gatewayRegion;
        $this->configValueFactory = $configValueFactory;
    }

    /**
     * @param Observer $observer
     * @return AddGatewayRegionObserver
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        $website = $observer->getEvent()->getData('website');
        $store = $observer->getEvent()->getData('store');
        $changedPaths = $observer->getEvent()->getData('changed_paths');

        $haystack = [
            SezzleIdentity::XML_PATH_PUBLIC_KEY,
            SezzleIdentity::XML_PATH_PRIVATE_KEY,
            SezzleIdentity::XML_PATH_PAYMENT_MODE
        ];

        if (count(array_intersect($haystack, $changedPaths)) <= 0) {
            return $this;
        }

        $scope = "default";
        $scopeId = 0;
        if ($website) {
            $scope = StoreScopeInterface::SCOPE_WEBSITES;
            $scopeId = $website;
        } elseif ($store) {
            $scope = StoreScopeInterface::SCOPE_WEBSITES;
            $scopeId = $store;
        }

        $gatewayRegion = $this->gatewayRegion->getValue();
        $this->configValueFactory->create()->load(
            SezzleIdentity::XML_PATH_GATEWAY_REGION,
            'path'
        )->setValue(
            $gatewayRegion
        )->setPath(
            SezzleIdentity::XML_PATH_GATEWAY_REGION
        )->setScope(
            $scope
        )->setScopeId(
            $scopeId
        )->save();

        return $this;
    }
}
