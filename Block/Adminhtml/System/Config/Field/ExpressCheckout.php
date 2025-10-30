<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Block\Adminhtml\System\Config\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Sezzle\Sezzlepay\Gateway\Config\Config;
use Sezzle\Sezzlepay\Gateway\Http\TransferFactory;
use Sezzle\Sezzlepay\Gateway\Http\Client;
use Sezzle\Sezzlepay\Helper\Data as Helper;

/**
 * Class ExpressCheckout
 * @package Sezzle\Sezzlepay\Block\Adminhtml\System\Config\Field
 */
class ExpressCheckout extends Field
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var TransferFactory
     */
    private $transferFactory;

    /**
     * @var Client
     */
    private $client;

    /**
     * ExpressCheckout constructor.
     * @param Context $context
     * @param Config $config
     * @param Helper $helper
     * @param TransferFactory $transferFactory
     * @param Client $client
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $config,
        Helper $helper,
        TransferFactory $transferFactory,
        Client $client,
        array $data = []
    ) {
        $this->config = $config;
        $this->helper = $helper;
        $this->transferFactory = $transferFactory;
        $this->client = $client;
        parent::__construct($context, $data);
    }

    /**
     * Render Element - hide field if feature flag is not enabled
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        if (!$this->config->isExpressCheckoutFeatureFlagEnabled()) {
            return '';
        }

        return parent::render($element);
    }

}
