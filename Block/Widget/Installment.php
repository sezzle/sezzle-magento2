<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Block\Widget;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Sezzle\Sezzlepay\Model\System\Config\Container\SezzleConfigInterface;

/**
 * Class Installment
 * @package Sezzle\Sezzlepay\Block\Widget
 */
class Installment extends Template
{

    /**
     * @var SezzleConfigInterface
     */
    private $sezzleConfig;

    /**
     * Installment constructor.
     * @param Template\Context $context
     * @param SezzleConfigInterface $sezzleConfig
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        SezzleConfigInterface $sezzleConfig,
        array $data = [])
    {
        $this->sezzleConfig = $sezzleConfig;
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
            return $this->sezzleConfig->isInstallmentWidgetEnabled()
                && $this->sezzleConfig->isEnabled();
        } catch (NoSuchEntityException $e) {
            return false;
        }
    }

    /**
     * Get Price Path
     *
     * @return string
     */
    public function getPricePath()
    {
        try {
            return $this->sezzleConfig->getInstallmentWidgetPricePath();
        } catch (NoSuchEntityException $e) {
            return "";
        }
    }
}
