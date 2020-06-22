<?php
namespace Sezzle\Payment\Model\System\Config\Source\Payment;

use Magento\Directory\Model\ResourceModel\Country\CollectionFactory;
use Magento\Framework\Option\ArrayInterface;
use Sezzle\Payment\Model\System\Config\Config;

/**
 * Source model for merchant countries supported by Sezzle
 */
class MerchantCountry implements ArrayInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var CollectionFactory
     */
    private $countryCollectionFactory;

    /**
     * @param Config $config
     * @param CollectionFactory $countryCollectionFactory
     */
    public function __construct(
        Config $config,
        CollectionFactory $countryCollectionFactory
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
        return $this->countryCollectionFactory->create()->addCountryCodeFilter(
            $supported,
            'iso2'
        )->loadData()->toOptionArray(false);
    }
}
