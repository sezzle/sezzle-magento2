<?php
/**
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */
namespace Sezzle\Sezzlepay\Setup\Patch\Data;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Sezzle\Sezzlepay\Model\System\Config\Backend\LogCron;
use Sezzle\Sezzlepay\Gateway\Config\Config;

class InitializeLogCronSchedule implements DataPatchInterface
{
    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param WriterInterface $configWriter
     * @param Config $config
     */
    public function __construct(
        WriterInterface $configWriter,
        Config $config
    ) {
        $this->configWriter = $configWriter;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        // Check if log sending is enabled
        $isEnabled = $this->config->isLogsSendingToSezzleAllowed();

        // Set cron expression based on current configuration
        $cronExpr = $isEnabled ? LogCron::CRON_EXPRESSION : '';

        $this->configWriter->save(
            LogCron::CRON_STRING_PATH,
            $cronExpr,
            'default',  // scope
            0           // scope_id
        );

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
