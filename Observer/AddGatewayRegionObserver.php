<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Observer;

use Exception;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\InputException;
use Sezzle\Sezzlepay\Model\System\Config\Config;
use Sezzle\Sezzlepay\Model\System\Config\Container\SezzleConfigInterface;
use Sezzle\Sezzlepay\Model\System\Config\Container\SezzleIdentity;

/**
 * Class AddGatewayRegionObserver
 * @package Sezzle\Sezzlepay\Observer
 */
class AddGatewayRegionObserver implements ObserverInterface
{
    /**
     * @var SezzleConfigInterface
     */
    private $sezzleConfig;

    /**
     * AddGatewayRegionObserver constructor.
     * @param SezzleConfigInterface $sezzleConfig
     */
    public function __construct(
        SezzleConfigInterface $sezzleConfig
    ) {
        $this->sezzleConfig = $sezzleConfig;
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
            $this->sezzleConfig->setGatewayRegion($website, $store);
        } catch (Exception $e) {
            throw new InputException(__('Sezzle API Keys not validated'));
        }

        return $this;
    }
}
