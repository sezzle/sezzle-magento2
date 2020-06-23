<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Payment
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Payment\Block\Adminhtml\System\Config;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NoSuchEntityException;
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
     * @var \Sezzle\Payment\Helper\Data
     */
    private $sezzleHelper;

    /**
     * SezzleRegisterAdmin constructor.
     *
     * @param Context $context
     * @param Data $jsonHelper
     * @param Config $config
     * @param \Sezzle\Payment\Helper\Data $sezzleHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $jsonHelper,
        Config $config,
        \Sezzle\Payment\Helper\Data $sezzleHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
        $this->jsonHelper = $jsonHelper;
        $this->sezzleHelper = $sezzleHelper;
    }

    /**
     * Return config settings
     */
    public function getJsonConfig()
    {
        return $this->jsonHelper->jsonEncode($this->config->getSezzleJsonConfig());
    }

    /**
     * Get Sezzle Module Version
     * @return string
     * @throws FileSystemException
     */
    public function getSezzleModuleVersion()
    {
        return $this->sezzleHelper->getVersion();
    }
}
