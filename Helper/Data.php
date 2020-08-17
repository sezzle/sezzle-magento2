<?php

namespace Sezzle\Sezzlepay\Helper;

use Sezzle\Sezzlepay\Model\Config\Container\SezzleApiConfigInterface;

/**
 * Sezzle Helper
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const SEZZLE_LOG_FILE_PATH = '/var/log/sezzlepay.log';

    /**
     * @var SezzleApiConfigInterface
     */
    private $sezzleApiConfig;

    /**
     * Initialize dependencies.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param SezzleApiConfigInterface $sezzleApiConfig
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        SezzleApiConfigInterface $sezzleApiConfig
    ) {
        $this->sezzleApiConfig = $sezzleApiConfig;
        parent::__construct($context);
    }



    /**
     * Returns formated price.
     *
     * @param string $price
     * @param string $currencyCode
     * @return string
     */
    public function formatPrice($price, $currencyCode = '')
    {
        $formatedPrice = number_format($price, 2, '.', '');

        if ($currencyCode) {
            return $formatedPrice . ' ' . $currencyCode;
        } else {
            return $formatedPrice;
        }
    }

    /**
     * Dump Sezzle log actions
     *
     * @param string $msg
     * @return void
     */
    public function logSezzleActions($data = null)
    {
        if ($this->sezzleApiConfig->isLogTrackerEnabled()) {
            $writer = new \Zend\Log\Writer\Stream(BP . self::SEZZLE_LOG_FILE_PATH);
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);
            $logger->info($data);
        }
    }
}
