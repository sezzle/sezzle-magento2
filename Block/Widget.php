<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Block;

use Magento\Framework\View\Element\Template;
use Sezzle\Sezzlepay\Model\Config\Container\SezzleApiConfigInterface;

/**
 * Class Widget
 * @package Sezzle\Sezzlepay\Block
 */
class Widget extends Template
{

    /**
     * @var SezzleApiConfigInterface
     */
    private $sezzleApiConfig;

    /**
     * ProductWidget constructor.
     *
     * @param Template\Context $context
     * @param SezzleApiConfigInterface $sezzleApiConfig
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        SezzleApiConfigInterface $sezzleApiConfig,
        array $data
    ) {
        $this->sezzleApiConfig = $sezzleApiConfig;
        parent::__construct($context, $data);
    }

    /**
     * Get Widget Script for PDP status
     *
     * @return string
     */
    public function isWidgetScriptAllowedForPDP()
    {
        return $this->sezzleApiConfig->isWidgetScriptAllowedForPDP();
    }

    /**
     * Get Widget Script for Cart Page status
     *
     * @return string
     */
    public function isWidgetScriptAllowedForCartPage()
    {
        return $this->sezzleApiConfig->isWidgetScriptAllowedForCartPage();
    }

    /**
     * Get merchant id
     *
     * @return string
     */
    public function getMerchantID()
    {
        return $this->sezzleApiConfig->getMerchantId();
    }
}
