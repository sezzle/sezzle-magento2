<?php
/**
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */
namespace Sezzle\Sezzlepay\Cron;

class SyncSettlementReports
{
    /**
     * @var \Magento\Paypal\Model\Report\SettlementFactory
     */
    protected $_settlementFactory;

    /**
     * Constructor
     *
     * @param \Magento\Paypal\Model\Report\SettlementFactory $settlementFactory
     */
    public function __construct(
        \Magento\Paypal\Model\Report\SettlementFactory $settlementFactory
    ) {
        $this->_settlementFactory = $settlementFactory;
    }

    /**
     * Goes to reports.paypal.com and fetches Settlement reports.
     *
     * @return void
     * @throws \Exception
     */
    public function execute()
    {
        /** @var \Magento\Paypal\Model\Report\Settlement $reports */
        $reports = $this->_settlementFactory->create();
        /* @var $reports \Magento\Paypal\Model\Report\Settlement */
        $credentials = $reports->getSftpCredentials(true);
        foreach ($credentials as $config) {
            $reports->fetchAndSave(\Magento\Paypal\Model\Report\Settlement::createConnection($config));
        }
    }
}
