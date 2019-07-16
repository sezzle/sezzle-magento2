<?php
namespace Sezzle\Sezzlepay\Model\System\Config\Source;

/**
 * Source model for merchant countries supported by Sezzle
 */
class MerchantCountry implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Sezzle\Sezzlepay\Model\System\Config
     */
    private $config;

    /**
     * @var \Magento\Directory\Model\ResourceModel\Country\CollectionFactory
     */
    private $countryCollectionFactory;

    /**
     * @param \Sezzle\Sezzlepay\Model\System\Config $config
     * @param \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory
     */
    public function __construct(
        \Sezzle\Sezzlepay\Model\System\Config $config,
        \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory
    ) {
        $this->config = $config;
        $this->countryCollectionFactory = $countryCollectionFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        $supported = $this->config->getSupportedMerchantCountryCodes();
        $options = $this->countryCollectionFactory->create()->addCountryCodeFilter(
            $supported,
            'iso2'
        )->loadData()->toOptionArray(false);

        return $options;
    }
}
