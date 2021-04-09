<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Observer;

use Exception;
use Magento\Framework\App\Config\ValueFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\InputException;
use Sezzle\Sezzlepay\Model\System\Config\Config;
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
     * @var Config
     */
    private $config;

    /**
     * AddGatewayRegionObserver constructor.
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * @param Observer $observer
     * @return AddGatewayRegionObserver
     * @throws InputException
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

        try {
            $this->config->setGatewayRegion($website, $store);
        } catch (Exception $e) {
            throw new InputException(__('Sezzle API Keys not validated'));
        }

        return $this;
    }
}
