<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */
declare(strict_types=1);

namespace Sezzle\Sezzlepay\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetup;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Sezzle\Sezzlepay\Model\Sezzle;
use Sezzle\Sezzlepay\Model\Tokenize;
use Zend_Validate_Exception;

/**
 * Patch is mechanism, that allows to do atomic upgrade data changes
 */
class AddCustomerAttributes implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * @var CustomerSetupFactory
     */
    protected $customerSetupFactory;

    /**
     * @var AttributeSetFactory
     */
    private $attributeSetFactory;

    /**
     * @var string[]
     */
    protected $sezzleCustomerAttributes = [
        Tokenize::ATTR_SEZZLE_CUSTOMER_UUID => [
            'input' => 'text',
            'label' => 'Sezzle Tokenize Status',
        ],
        Tokenize::ATTR_SEZZLE_TOKEN_STATUS => [
            'input' => 'boolean',
            'label' => 'Sezzle Customer UUID'
        ],
        Tokenize::ATTR_SEZZLE_CUSTOMER_UUID_EXPIRATION => [
            'input' => 'text',
            'label' => 'Sezzle Customer UUID Expiration'
        ],
        Sezzle::ADDITIONAL_INFORMATION_KEY_CREATE_ORDER_LINK => [
            'input' => 'text',
            'label' => 'Sezzle Order Create Link By Customer UUID',
        ],
        Sezzle::ADDITIONAL_INFORMATION_KEY_GET_CUSTOMER_LINK => [
            'input' => 'text',
            'label' => 'Sezzle Get Customer Link By Customer UUID',
        ]
    ];

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CustomerSetupFactory $customerSetupFactory
     * @param AttributeSetFactory $attributeSetFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CustomerSetupFactory $customerSetupFactory,
        AttributeSetFactory $attributeSetFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->customerSetupFactory = $customerSetupFactory;
        $this->attributeSetFactory = $attributeSetFactory;
    }

    /**
     * Do Upgrade
     *
     * @return void
     * @throws LocalizedException
     * @throws Zend_Validate_Exception
     */
    public function apply()
    {
        foreach ($this->sezzleCustomerAttributes as $attributeCode => $attribute) {
            $this->addCustomerAttribute($attributeCode, $attribute['input'], $attribute['label']);
        }
    }

    /**
     * @param string $attributeCode
     * @param string $input
     * @param string $attributeLabel
     * @throws LocalizedException
     * @throws Zend_Validate_Exception
     */
    private function addCustomerAttribute($attributeCode, $input, $attributeLabel)
    {
        /** @var CustomerSetup $customerSetup */
        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $customerEntity = $customerSetup->getEavConfig()->getEntityType('customer');
        $attributeSetId = $customerEntity->getDefaultAttributeSetId();

        $attributeSet = $this->attributeSetFactory->create();
        $attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);

        $customerSetup->addAttribute(Customer::ENTITY, $attributeCode, [
            'type' => $input == 'boolean' ? 'int' : 'varchar',
            'label' => $attributeLabel,
            'input' => $input,
            'required' => false,
            'visible' => false,
            'user_defined' => false,
            'position' => 999,
            'system' => 0,
        ]);

        $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, $attributeCode)
            ->addData([
                'attribute_set_id' => $attributeSetId,
                'attribute_group_id' => $attributeGroupId
            ]);

        $attribute->save();
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }
}
