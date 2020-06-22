<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Payment
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Payment\Setup;

use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Eav\Model\Entity\Attribute\Set as AttributeSet;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Quote\Setup\QuoteSetupFactory;
use Magento\Sales\Setup\SalesSetupFactory;
use Sezzle\Payment\Model\Sezzle;
use Sezzle\Payment\Model\Tokenize;

/**
 * @codeCoverageIgnore
 */
class UpgradeData implements UpgradeDataInterface
{

    /**
     * @var CustomerSetupFactory
     */
    protected $customerSetupFactory;

    /**
     * @var AttributeSetFactory
     */
    private $attributeSetFactory;
    /**
     * @var SalesSetupFactory
     */
    private $salesSetupFactory;
    /**
     * @var QuoteSetupFactory
     */
    private $quoteSetupFactory;

    /**
     * @param CustomerSetupFactory $customerSetupFactory
     * @param AttributeSetFactory $attributeSetFactory
     * @param SalesSetupFactory $salesSetupFactory
     * @param QuoteSetupFactory $quoteSetupFactory
     */
    public function __construct(
        CustomerSetupFactory $customerSetupFactory,
        AttributeSetFactory $attributeSetFactory,
        SalesSetupFactory $salesSetupFactory,
        QuoteSetupFactory $quoteSetupFactory
    ) {
        $this->customerSetupFactory = $customerSetupFactory;
        $this->attributeSetFactory = $attributeSetFactory;
        $this->salesSetupFactory = $salesSetupFactory;
        $this->quoteSetupFactory = $quoteSetupFactory;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '2.0.0', '<')) {
            $customerAttributesToAdd = [
                Tokenize::ATTR_SEZZLE_CUSTOMER_UUID => [
                        'input' => 'boolean',
                        'label' => 'Sezzle Tokenize Status',
                ],
                Tokenize::ATTR_SEZZLE_TOKEN_STATUS => [
                        'input' => 'text',
                        'label' => 'Sezzle Customer UUID'
                ],
                Tokenize::ATTR_SEZZLE_CUSTOMER_UUID_EXPIRATION => [
                        'input' => 'text',
                        'label' => 'Sezzle Customer UUID Expiration'
                ],
                Sezzle::ADDITIONAL_INFORMATION_KEY_CREATE_ORDER_LINK => [
                    'input' => 'text',
                    'label' => 'Sezzle Order Create Link By Customer UUID',
                ]
            ];
            foreach ($customerAttributesToAdd as $attributeCode => $attribute) {
                $this->addCustomerAttribute($setup, $attributeCode, $attribute['input'], $attribute['label']);
            }
        }
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param string $attributeCode
     * @param string $input
     * @param string $attributeLabel
     */
    private function addCustomerAttribute(ModuleDataSetupInterface $setup, $attributeCode, $input, $attributeLabel)
    {
        /** @var CustomerSetup $customerSetup */
        $customerSetup = $this->customerSetupFactory->create(['setup' => $setup]);

        $customerEntity = $customerSetup->getEavConfig()->getEntityType('customer');
        $attributeSetId = $customerEntity->getDefaultAttributeSetId();

        /** @var $attributeSet AttributeSet */
        $attributeSet = $this->attributeSetFactory->create();
        $attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);

        $customerSetup->addAttribute(Customer::ENTITY, $attributeCode, [
            'type' => $input == 'boolean' ? 'int' : 'varchar',
            'label' => $attributeLabel,
            'input' => $input,
            'required' => false,
            'visible' => false,
            'user_defined' => false,
            'position' =>999,
            'system' => 0,
        ]);

        $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, $attributeCode)
            ->addData([
                'attribute_set_id' => $attributeSetId,
                'attribute_group_id' => $attributeGroupId
                ]);

        $attribute->save();
    }
}
