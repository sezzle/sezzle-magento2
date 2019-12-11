<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Observer;

use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface as Logger;
use Sezzle\Sezzlepay\Model\Config\Container\SezzleApiConfigInterface;

/**
 * Class MethodAvailabilityObserver
 * @package Sezzle\Sezzlepay\Observer
 */
class MethodAvailabilityObserver implements ObserverInterface
{
    const PAYMENT_CODE = 'sezzlepay';

    /**
     * MethodAvailabilityObserver constructor.
     * @param SezzleApiConfigInterface $sezzleApiIdentity
     * @param Logger $logger
     */
    public function __construct(
        SezzleApiConfigInterface $sezzleApiIdentity,
        Logger $logger
    ) {
        $this->sezzleApiIdentity = $sezzleApiIdentity;
        $this->logger = $logger;
    }

    /**
     * Hide the method if merchant id, public key & private key are not present
     * and if grand total is less than min checkout amount(grand total)
     * 
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $result = $observer->getEvent()->getResult();
        $quote = $observer->getEvent()->getQuote();
        $methodInstance = $observer->getEvent()->getMethodInstance();

        $merchantId = $this->sezzleApiIdentity->getMerchantId();
        $publicKey = $this->sezzleApiIdentity->getPublicKey();
        $privateKey = $this->sezzleApiIdentity->getPrivateKey();
        $minCheckoutAmount = $this->sezzleApiIdentity->getMinCheckoutAmount();

        if (($methodInstance->getCode() == self::PAYMENT_CODE)
            && ((!$merchantId || !$publicKey || !$privateKey)
            || ($quote
            && ($quote->getBaseGrandTotal() < $minCheckoutAmount)))) {
            $result->setData('is_available', false);
        }
    }
}
