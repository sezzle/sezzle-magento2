<?php

namespace Sezzle\Sezzlepay\Observer;

use Exception;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use Sezzle\Sezzlepay\Gateway\Config\Config;
use Sezzle\Sezzlepay\Helper\Data as SezzleHelper;
use Sezzle\Sezzlepay\Model\Ui\ConfigProvider;

class PaymentMethodAvailabilityObserver implements ObserverInterface
{

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var SezzleHelper
     */
    private SezzleHelper $helper;

    /**
     * PaymentMethodAvailabilityObserver constructor
     * @param Config $config
     * @param SezzleHelper $helper
     */
    public function __construct(
        Config       $config,
        SezzleHelper $helper
    )
    {
        $this->config = $config;
        $this->helper = $helper;
    }

    /**
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(EventObserver $observer)
    {
        $event = $observer->getEvent();
        $methodInstance = $event->getMethodInstance();
        if ($methodInstance->getCode() !== ConfigProvider::CODE) {
            return $this;
        }

        /** @var Quote $quote */
        $quote = $event->getQuote();

        /** @var DataObject $result */
        $result = $event->getResult();

        try {
            $merchantUUID = $this->config->getMerchantUUID();
            $publicKey = $this->config->getPublicKey();
            $privateKey = $this->config->getPrivateKey();
            $minCheckoutAmount = $this->config->getMinCheckoutAmount();
            $isAvailable = true;

            switch (true) {
                case ($quote && ($quote->getBaseGrandTotal() < $minCheckoutAmount)):
                case (!$merchantUUID || !$publicKey || !$privateKey):
                    $isAvailable = false;
            }
        } catch (Exception $e) {
            $isAvailable = false;
            $this->helper->logSezzleActions('Payment method not available.' . $e->getMessage());
        }

        $result->setData('is_available', $isAvailable);
        return $this;
    }
}
