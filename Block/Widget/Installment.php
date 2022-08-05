<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Block\Widget;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Sezzle\Sezzlepay\Gateway\Config\Config;

/**
 * Class Installment
 * @package Sezzle\Sezzlepay\Block\Widget
 */
class Installment extends Template
{

    /**
     * @var Config
     */
    private $config;

    /**
     * Installment constructor.
     * @param Template\Context $context
     * @param Config $config
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Config           $config,
        array            $data = []
    )
    {
        $this->config = $config;
        parent::__construct($context, $data);
    }

    /**
     * Get Installment Widget Status
     *
     * @return string
     */
    public function isInstallmentWidgetEnabled()
    {
        try {
            return $this->config->isEnabled() && $this->config->isInstallmentWidgetEnabled();
        } catch (NoSuchEntityException|InputException $e) {
            return false;
        }
    }
}
