<?php

namespace Sezzle\Sezzlepay\Model\Ui;

use Exception;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\CurrencyInterface;
use Magento\Framework\Module\Manager;
use Sezzle\Sezzlepay\Model\Sezzle;
use Sezzle\Sezzlepay\Model\System\Config\Container\SezzleConfigInterface;
use Sezzle\Sezzlepay\Model\Tokenize;

/**
 * ConfigProvider
 */
class ConfigProvider implements ConfigProviderInterface
{

    const CODE = "sezzlepay";

    /**
     * @var SezzleConfigInterface
     */
    private $sezzleConfig;

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
     * @var CurrencyInterface
     */
    private $localeCurrency;

    /**
     * ConfigProvider constructor.
     * @param SezzleConfigInterface $sezzleConfig
     * @param Session $checkoutSession
     * @param Tokenize $tokenizeModel
     * @param Manager $moduleManager
     * @param CurrencyInterface $localeCurrency
     */
    public function __construct(
        SezzleConfigInterface $sezzleConfig,
        Session $checkoutSession,
        Tokenize $tokenizeModel,
        Manager $moduleManager,
        CurrencyInterface $localeCurrency
    ) {
        $this->sezzleConfig = $sezzleConfig;
        $this->checkoutSession = $checkoutSession;
        $this->tokenizeModel = $tokenizeModel;
        $this->moduleManager = $moduleManager;
        $this->localeCurrency = $localeCurrency;
    }

    /**
     * @return array
     * @throws NoSuchEntityException|LocalizedException
     * @throws Exception
     */
    public function getConfig(): array
    {
        $quote = $this->checkoutSession->getQuote();
        $isTokenizeCheckoutAllowed = $this->tokenizeModel->isCustomerUUIDValid($quote);
        $isInContextCheckout = (bool)$this->sezzleConfig->isInContextModeEnabled();
        $allowInContextCheckout = $isInContextCheckout && !$isTokenizeCheckoutAllowed;

        return [
            'payment' => [
                self::CODE => [
                    'methodCode' => Sezzle::PAYMENT_CODE,
                    'allowInContextCheckout' => $allowInContextCheckout,
                    'inContextMode' => $this->sezzleConfig->getInContextMode(),
                    'inContextTransactionMode' => $this->sezzleConfig->getPaymentMode(),
                    'inContextApiVersion' => 'v2',
                    'isAheadworksCheckoutEnabled' => $this->moduleManager->isEnabled('Aheadworks_OneStepCheckout'),
                    'installmentWidgetPricePath' => $this->sezzleConfig->getInstallmentWidgetPricePath(),
                    'currencySymbol' => $this->localeCurrency->getCurrency($quote->getBaseCurrencyCode())->getSymbol(),
                    'gatewayRegion' => $this->sezzleConfig->getGatewayRegion(),
                    'logo' => $this->sezzleConfig->getLogo(),
                ]
            ]
        ];
    }
}
