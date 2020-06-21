<?php
namespace Sezzle\Payment\Setup;

use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Eav\Model\Entity\Attribute\Set as AttributeSet;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Quote\Setup\QuoteSetup;
use Magento\Quote\Setup\QuoteSetupFactory;
use Magento\Sales\Setup\SalesSetup;
use Magento\Sales\Setup\SalesSetupFactory;

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
                'sezzle_tokenize_status' => [
                        'input' => 'boolean',
                        'label' => 'Sezzle Tokenize Status',
                ],
                'sezzle_customer_uuid' => [
                        'input' => 'text',
                        'label' => 'Sezzle Customer UUID'
                ],
                'sezzle_customer_uuid_expiration' => [
                        'input' => 'text',
                        'label' => 'Sezzle Customer UUID Expiration'
                ],
                'sezzle_create_order_link' => [
                    'input' => 'text',
                    'label' => 'Sezzle Order Create Link By Customer UUID',
                ]
            ];

            $quoteAttributesToAdd = [
                'sezzle_information' => [
                    'input' => 'text',
                    'label' => 'Sezzle Information',
                ]
            ];
            foreach ($customerAttributesToAdd as $attributeCode => $attribute) {
                $this->addCustomerAttribute($setup, $attributeCode, $attribute['input'], $attribute['label']);
            }

            /** @var SalesSetup $salesInstaller */
            $salesInstaller = $this->salesSetupFactory
                ->create(
                    [
                        'resourceName' => 'sales_setup',
                        'setup' => $setup
                    ]
                );
            /** @var QuoteSetup $quoteInstaller */
            $quoteInstaller = $this->quoteSetupFactory
                ->create(
                    [
                        'resourceName' => 'quote_setup',
                        'setup' => $setup
                    ]
                );

            foreach ($quoteAttributesToAdd as $attributeCode => $attribute) {
                $this->addQuoteAttribute($quoteInstaller, $attributeCode, $attribute['input']);
                $this->addOrderAttribute($salesInstaller, $attributeCode, $attribute['input']);
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

    /**
     * @param QuoteSetup $quoteInstaller
     * @param string $attributeCode
     * @param string $input
     */
    private function addQuoteAttribute(QuoteSetup $quoteInstaller, $attributeCode, $input)
    {
        $quoteInstaller->addAttribute('quote', $attributeCode, ['type' => $input]);
    }

    /**
     * @param SalesSetup $salesInstaller
     * @param string $attributeCode
     * @param string $input
     */
    public function addOrderAttribute(SalesSetup $salesInstaller, $attributeCode, $input)
    {
        $salesInstaller->addAttribute('order', $attributeCode, ['type' => $input]);
    }
}
