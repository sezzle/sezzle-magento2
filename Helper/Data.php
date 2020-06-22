<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Payment
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Payment\Helper;

use Sezzle\Payment\Model\System\Config\Container\SezzleApiConfigInterface;

/**
 * Sezzle Helper
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const SEZZLE_LOG_FILE_PATH = '/var/log/sezzle.log';

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
