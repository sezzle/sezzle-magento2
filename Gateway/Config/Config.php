<?php

namespace Sezzle\Sezzlepay\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Config\Config as PaymentConfig;
use Sezzle\Sezzlepay\Model\StoreConfigResolver;
use Magento\Framework\Locale\Resolver;

/**
 * Config
 */
class Config extends PaymentConfig
{

    const KEY_ACTIVE = 'active';
    const KEY_PUBLIC_KEY = 'public_key';
    const KEY_PRIVATE_KEY = 'private_key';
    const KEY_PAYMENT_MODE = 'payment_mode';
    const KEY_MERCHANT_UUID = 'merchant_id';
    const KEY_PAYMENT_ACTION = 'payment_action';
    const KEY_MIN_CHECKOUT_AMOUNT = 'min_checkout_amount';
    const KEY_TOKENIZE = 'tokenize';

    const KEY_WIDGET_PDP = 'widget_pdp';
    const KEY_WIDGET_CART = 'widget_cart';
    const KEY_WIDGET_TICKET_CREATED_AT = 'widget_ticket_created_at';
    const KEY_WIDGET_INSTALLMENT = 'widget_installment';
    const KEY_WIDGET_INSTALLMENT_PRICE = 'widget_installment_price_path';

    const KEY_INCONTEXT_ACTIVE = 'active_in_context';
    const KEY_INCONTEXT_MODE = 'in_context_mode';

    const KEY_LOG_TRACKER = 'log_tracker';
    const KEY_CRON_LOGS = 'send_logs_via_cron';

    const KEY_SETTLEMENT_REPORTS = 'settlement_reports';
    const KEY_SETTLEMENT_REPORTS_RANGE = 'settlement_reports_range';

    const PAYMENT_MODE_SANDBOX = 'sandbox';
    const PAYMENT_MODE_LIVE = 'live';

    const INCONTEXT_MODE_IFRAME = 'iframe';
    const INCONTEXT_MODE_POPUP = 'popup';

    const API_VERSION_V1 = 'v1';
    const API_VERSION_V2 = 'v2';

    const GATEWAY_URL = 'https://%sgateway.sezzle.com/%s';
    const WIDGET_URL = 'https://widget.sezzle.com/%s';
    public static $imageSrc = [
           'en' => 'https://media.sezzle.com/branding/sezzle-logos/sezzle-pay-over-time-no-interest@2x.png',
           'fr' => 'https://media.sezzle.com/stripo/guids/CABINET_3a2c9b1a0b2efa35092eb0ce3ff5b254/images/sezzlecheckout_french_1_wFv.png'
       ];

   /**
      * @var Resolver
      */
     private $localeResolver;

   /**
     * @var StoreConfigResolver
     */
    private $storeConfigResolver;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * Config constructor.
     * @param StoreConfigResolver $storeConfigResolver
     * @param ScopeConfigInterface $scopeConfig
     * @param UrlInterface $urlBuilder
     * @param Resolver $localeResolver
     * @param null $methodCode
     * @param string $pathPattern
     */


    public function __construct(
        StoreConfigResolver  $storeConfigResolver,
        ScopeConfigInterface $scopeConfig,
        UrlInterface         $urlBuilder,
        Resolver             $localeResolver,
                             $methodCode = null,
        string               $pathPattern = self::DEFAULT_PATH_PATTERN
    )
    {
        parent::__construct($scopeConfig, $methodCode, $pathPattern);
        $this->storeConfigResolver = $storeConfigResolver;
        $this->urlBuilder = $urlBuilder;
        $this->localeResolver = $localeResolver;
    }

    /**
     * @param int|null $storeId
     * @return bool
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function isEnabled(int $storeId = null): bool
    {
        return (bool)$this->getValue(
            self::KEY_ACTIVE,
            $storeId ?? $this->storeConfigResolver->getStoreId()
        );
    }

    /**
     * @param int|null $storeId
     * @return string|null
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getPublicKey(int $storeId = null): ?string
    {
        return $this->getValue(
            self::KEY_PUBLIC_KEY,
            $storeId ?? $this->storeConfigResolver->getStoreId()
        );
    }

    /**
     * @param int|null $storeId
     * @return string|null
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getPrivateKey(int $storeId = null): ?string
    {
        return $this->getValue(
            self::KEY_PRIVATE_KEY,
            $storeId ?? $this->storeConfigResolver->getStoreId()
        );
    }

    /**
     * @param int|null $storeId
     * @return string|null
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getMerchantUUID(int $storeId = null): ?string
    {
        return $this->getValue(
            self::KEY_MERCHANT_UUID,
            $storeId ?? $this->storeConfigResolver->getStoreId()
        );
    }

    /**
     * @param int|null $storeId
     * @return string
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getPaymentMode(int $storeId = null): string
    {
        return $this->getValue(
            self::KEY_PAYMENT_MODE,
            $storeId ?? $this->storeConfigResolver->getStoreId()
        );
    }

    /**
     * @param int|null $storeId
     * @return string
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getPaymentAction(int $storeId = null): string
    {
        return $this->getValue(
            self::KEY_PAYMENT_ACTION,
            $storeId ?? $this->storeConfigResolver->getStoreId()
        );
    }

    /**
     * @param int|null $storeId
     * @return float|null
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getMinCheckoutAmount(int $storeId = null): ?float
    {
        return $this->getValue(
            self::KEY_MIN_CHECKOUT_AMOUNT,
            $storeId ?? $this->storeConfigResolver->getStoreId()
        );
    }

    /**
     * @param int|null $storeId
     * @return bool
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function isTokenizationEnabled(int $storeId = null): bool
    {
        return (bool)$this->getValue(
            self::KEY_TOKENIZE,
            $storeId ?? $this->storeConfigResolver->getStoreId()
        );
    }

    /**
     * @param int|null $storeId
     * @return bool
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function isWidgetEnabledForPDP(int $storeId = null): bool
    {
        return (bool)$this->getValue(
            self::KEY_WIDGET_PDP,
            $storeId ?? $this->storeConfigResolver->getStoreId()
        );
    }

    /**
     * @param int|null $storeId
     * @return bool
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function isWidgetEnabledForCart(int $storeId = null): bool
    {
        return (bool)$this->getValue(
            self::KEY_WIDGET_CART,
            $storeId ?? $this->storeConfigResolver->getStoreId()
        );
    }

    /**
     * @param int|null $storeId
     * @return string|null
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getWidgetTicketCreatedAt(int $storeId = null): ?string
    {
        return $this->getValue(
            self::KEY_WIDGET_TICKET_CREATED_AT,
            $storeId ?? $this->storeConfigResolver->getStoreId()
        );
    }

    /**
     * @param int|null $storeId
     * @return bool
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function isInstallmentWidgetEnabled(int $storeId = null): bool
    {
        return (bool)$this->getValue(
            self::KEY_WIDGET_INSTALLMENT,
            $storeId ?? $this->storeConfigResolver->getStoreId()
        );
    }

    /**
     * @param int|null $storeId
     * @return string
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getInstallmentWidgetPricePath(int $storeId = null): ?string
    {
        return !$this->isInstallmentWidgetEnabled() ? '' :
            $this->getValue(
                self::KEY_WIDGET_INSTALLMENT_PRICE,
                $storeId ?? $this->storeConfigResolver->getStoreId()
            );
    }

    /**
     * @param int|null $storeId
     * @return bool
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function isLogTrackerEnabled(int $storeId = null): bool
    {
        return (bool)$this->getValue(
            self::KEY_LOG_TRACKER,
            $storeId ?? $this->storeConfigResolver->getStoreId()
        );
    }

    /**
     * @param int|null $storeId
     * @return bool
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function isLogsSendingToSezzleAllowed(int $storeId = null): bool
    {
        return (bool)$this->getValue(
            self::KEY_CRON_LOGS,
            $storeId ?? $this->storeConfigResolver->getStoreId()
        );
    }

    /**
     * @param int|null $storeId
     * @return bool
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function isSettlementReportsEnabled(int $storeId = null): bool
    {
        return (bool)$this->getValue(
            self::KEY_SETTLEMENT_REPORTS,
            $storeId ?? $this->storeConfigResolver->getStoreId()
        );
    }

    /**
     * @param int|null $storeId
     * @return bool
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getSettlementReportsRange(int $storeId = null): bool
    {
        return (bool)$this->getValue(
            self::KEY_SETTLEMENT_REPORTS_RANGE,
            $storeId ?? $this->storeConfigResolver->getStoreId()
        );
    }

    /**
     * @param int|null $storeId
     * @return bool
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function isInContextModeActive(int $storeId = null): bool
    {
        return (bool)$this->getValue(
            self::KEY_INCONTEXT_ACTIVE,
            $storeId ?? $this->storeConfigResolver->getStoreId()
        );
    }

    /**
     * @param int|null $storeId
     * @return string|null
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getInContextMode(int $storeId = null): ?string
    {
        return $this->getValue(
            self::KEY_INCONTEXT_MODE,
            $storeId ?? $this->storeConfigResolver->getStoreId()
        );
    }

    /**
     * Get API endpoint
     *
     * @param int|null $storeId
     * @param string $version
     * @return string
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getGatewayURL(int $storeId = null, string $version = self::API_VERSION_V2): string
    {
        $replaceValue = $this->getPaymentMode($storeId) === self::PAYMENT_MODE_SANDBOX ? self::PAYMENT_MODE_SANDBOX . '.' : '';
        return sprintf(self::GATEWAY_URL, $replaceValue, $version);
    }

    /**
     * Get widget URL
     *
     * @param string $apiVersion
     * @return string
     */
    public function getWidgetURL(string $apiVersion): string
    {
        return sprintf(self::WIDGET_URL, $apiVersion);
    }

    /**
     * Get image source
     *
     * @return string
     */
    public function getImageSrc(): string
    {
       $locale = $this->localeResolver->getLocale();
       if ($locale === 'fr_FR' || $locale === 'fr_CA') {
        return self::$imageSrc['fr'];
          }
        return self::$imageSrc['en'];

    }

    /**
     * Get complete payment URL
     *
     * @return string
     */
    public function getCompleteURL(): string
    {
        return $this->urlBuilder->getUrl("sezzle/payment/complete/", ['_secure' => true]);
    }

    /**
     * Get cancel payment URL
     *
     * @return string
     */
    public function getCancelURL(): string
    {
        return $this->urlBuilder->getUrl("sezzle/payment/cancel/", ['_secure' => true]);
    }
}
