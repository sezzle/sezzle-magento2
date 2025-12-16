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

class LogCron extends Value
{
    const CRON_STRING_PATH = 'crontab/default/jobs/sezzle_sync_logs/schedule/cron_expr';

    const CRON_MODEL_PATH = 'payment/sezzlepay/send_logs_via_cron';

    /**
     * Cron expression for log sending (every 6 hours)
     */
    const CRON_EXPRESSION = '0 */6 * * *';

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
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
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
        
        // Use THIS instance's value - it's already been validated and saved
        if ($this->getValue()) {  // ← Use $this->getValue() instead
            $cronExprString = self::CRON_EXPRESSION;
        }
        
        $cronScheduleConfig = $this->configValueFactory->create()->load(
            self::CRON_STRING_PATH,
            'path'
        );
        
        $cronScheduleConfig->setValue(
            $cronExprString
        )->setPath(
            self::CRON_STRING_PATH
        )->save();

        return parent::afterSave();
    }
}
