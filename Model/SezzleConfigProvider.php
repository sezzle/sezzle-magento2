<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model;

use Exception;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Module\Manager;
use Sezzle\Sezzlepay\Helper\Data;
use Sezzle\Sezzlepay\Model\System\Config\Container\SezzleConfigInterface;

/**
 * Class SezzleConfigProvider
 * @package Sezzle\Sezzlepay\Model
 */
class SezzleConfigProvider implements ConfigProviderInterface
{

    /**
     * @var SezzleConfigInterface
     */
    private $sezzleConfig;
    /**
     * @var Data
     */
    private $sezzleHelper;

    /**
     * @var Session
     */
    private $checkoutSession;
    /**
     * @var Tokenize
     */
    private $tokenizeModel;
    /**
     * @var Manager
     */
    private $moduleManager;

    /**
     * SezzleConfigProvider constructor.
     * @param SezzleConfigInterface $sezzleConfig
     * @param Data $sezzleHelper
     * @param Session $checkoutSession
     * @param Tokenize $tokenizeModel
     * @param Manager $moduleManager
     */
    public function __construct(
        SezzleConfigInterface $sezzleConfig,
        Data $sezzleHelper,
        Session $checkoutSession,
        Tokenize $tokenizeModel,
        Manager $moduleManager
    ) {
        $this->sezzleHelper = $sezzleHelper;
        $this->sezzleConfig = $sezzleConfig;
        $this->checkoutSession = $checkoutSession;
        $this->tokenizeModel = $tokenizeModel;
        $this->moduleManager = $moduleManager;
    }

    /**
     * @return array
     * @throws NoSuchEntityException|LocalizedException
     * @throws Exception
     */
    public function getConfig()
    {
        $quote = $this->checkoutSession->getQuote();
        $isTokenizeCheckoutAllowed = $this->tokenizeModel->isCustomerUUIDValid($quote);
        $isInContextCheckout = (bool)$this->sezzleConfig->isInContextModeEnabled();
        $allowInContextCheckout = $isInContextCheckout && !$isTokenizeCheckoutAllowed;
        return [
            'payment' => [
                Sezzle::PAYMENT_CODE => [
                    'methodCode' => Sezzle::PAYMENT_CODE,
                    'allowInContextCheckout' => $allowInContextCheckout,
                    'inContextMode' => $this->sezzleConfig->getInContextMode(),
                    'inContextTransactionMode' => $this->sezzleConfig->getPaymentMode(),
                    'inContextApiVersion' => 'v2',
                    'isAheadworksCheckoutEnabled' => $this->moduleManager->isEnabled('Aheadworks_OneStepCheckout'),
                    'installmentWidgetPricePath' => $this->sezzleConfig->getInstallmentWidgetPricePath()
                ]
            ]
        ];
    }
}
