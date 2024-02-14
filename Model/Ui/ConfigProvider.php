<?php

namespace Sezzle\Sezzlepay\Model\Ui;

use Exception;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\CurrencyInterface;
use Magento\Framework\Module\Manager;
use Sezzle\Sezzlepay\Gateway\Config\Config;
use Sezzle\Sezzlepay\Model\Tokenize;

/**
 * ConfigProvider
 */
class ConfigProvider implements ConfigProviderInterface
{

    const CODE = "sezzlepay";

    /**
     * @var Config
     */
    private $config;

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
     * @param Config $config
     * @param Session $checkoutSession
     * @param Tokenize $tokenizeModel
     * @param Manager $moduleManager
     * @param CurrencyInterface $localeCurrency
     */
    public function __construct(
        Config            $config,
        Session           $checkoutSession,
        Tokenize          $tokenizeModel,
        Manager           $moduleManager,
        CurrencyInterface $localeCurrency
    )
    {
        $this->config = $config;
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
        $isInContextCheckout = $this->config->isInContextModeActive();
        $allowInContextCheckout = $isInContextCheckout && !$isTokenizeCheckoutAllowed;

        return [
            'payment' => [
                self::CODE => [
                    'methodCode' => self::CODE,
                    'publicKey' => $this->config->getPublicKey(),
                    'allowInContextCheckout' => $allowInContextCheckout,
                    'inContextMode' => $this->config->getInContextMode(),
                    'inContextTransactionMode' => $this->config->getPaymentMode(),
                    'inContextApiVersion' => Config::API_VERSION_V2,
                    'isAheadworksCheckoutEnabled' => $this->moduleManager->isEnabled('Aheadworks_OneStepCheckout'),
                    'installmentWidgetPricePath' => $this->config->getInstallmentWidgetPricePath(),
                    'currencySymbol' => $this->localeCurrency->getCurrency($quote->getBaseCurrencyCode())->getSymbol(),
                    'img_src' => $this->config->getImageSrc(),
                ]
            ]
        ];
    }
}
