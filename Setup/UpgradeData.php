<?php

namespace Sezzle\Sezzlepay\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Sales\Setup\SalesSetupFactory;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var SalesSetupFactory
     */
    protected $salesSetupFactory;

    /**
     * @param SalesSetupFactory $salesSetupFactory
     */
    public function __construct(
        SalesSetupFactory $salesSetupFactory
    ) {
        $this->salesSetupFactory = $salesSetupFactory;
    }
    /**
     * Upgrades DB for a module
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        /** @var \Magento\Sales\Setup\SalesSetup $salesInstaller */
        $salesInstaller = $this->salesSetupFactory->create(['resourceName' => 'sales_setup', 'setup' => $setup]);

        $setup->startSetup();
        if (version_compare($context->getVersion(), '1.1.0', '<')) {
            //Add multiple attributes to quote
            $entityAttributesCodes = [
                'is_captured' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                'is_refunded' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN

            ];

            foreach ($entityAttributesCodes as $code => $type) {
                $salesInstaller->addAttribute('order', $code, ['type' => $type, 'visible' => true, 'default' => 0, 'nullable' => true]);
            }
        }

        $setup->endSetup();
    }
}
