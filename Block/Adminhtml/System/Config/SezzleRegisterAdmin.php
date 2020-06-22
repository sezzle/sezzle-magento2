<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Payment
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Payment\Block\Adminhtml\System\Config;

use Magento\Framework\Json\Helper\Data;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Sezzle\Payment\Model\System\Config\Config;

class SezzleRegisterAdmin extends Template
{
    /**
     * @var Config
     */
    private $config;
    /**
     * @var Data
     */
    private $jsonHelper;

    /**
     * SezzleRegisterAdmin constructor.
     *
     * @param Context $context
     * @param Config $config
     * @param Data $jsonHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $jsonHelper,
        Config $config,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
        $this->jsonHelper = $jsonHelper;
    }

    /**
     * Return config settings
     */
    public function getJsonConfig()
    {
        return $this->jsonHelper->jsonEncode($this->config->getSezzleJsonConfig());
    }
}
