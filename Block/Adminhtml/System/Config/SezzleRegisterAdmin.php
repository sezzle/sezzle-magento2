<?php

namespace Sezzle\Payment\Block\Adminhtml\System\Config;

class SezzleRegisterAdmin extends \Magento\Framework\View\Element\Template
{
    /**
     * @var Config
     */
    private $config;
    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    private $jsonHelper;

    /**
     * SezzleRegisterAdmin constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Sezzle\Payment\Model\System\Config $config
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Sezzle\Payment\Model\System\Config $config,
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
