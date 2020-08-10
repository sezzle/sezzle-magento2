<?php
/**
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */
namespace Sezzle\Sezzlepay\Model\System\Config\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\App\Config\ValueFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

class Cron extends Value
{
    const CRON_STRING_PATH = 'crontab/default/jobs/sezzle_sync_settlement_reports/schedule/cron_expr';

    const CRON_MODEL_PATH_INTERVAL = 'payment/sezzlepay/settlement_reports_schedule';

    /**
     * @var ValueFactory
     */
    protected $configValueFactory;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param ValueFactory $configValueFactory
     * @param AbstractResource $resource
     * @param AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        ValueFactory $configValueFactory,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->configValueFactory = $configValueFactory;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * Cron settings after save
     *
     * @return $this
     */
    public function afterSave()
    {
        $cronExprString = '';
        $time = explode(
            ',',
            $this->configValueFactory->create()->load('payment/sezzlepay/settlement_reports_time', 'path')->getValue()
        );

        if ($this->configValueFactory->create()->load('payment/sezzlepay/settlement_reports_auto_sync', 'path')->getValue()) {
            $interval = $this->configValueFactory->create()->load(self::CRON_MODEL_PATH_INTERVAL, 'path')->getValue();
            $cronExprString = "{$time[1]} {$time[0]} */{$interval} * *";
        }

        $this->configValueFactory->create()->load(
            self::CRON_STRING_PATH,
            'path'
        )->setValue(
            $cronExprString
        )->setPath(
            self::CRON_STRING_PATH
        )->save();

        return parent::afterSave();
    }
}
