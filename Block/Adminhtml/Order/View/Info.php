<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Payment
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */
namespace Sezzle\Payment\Block\Adminhtml\Order\View;

use Magento\Framework\Exception\LocalizedException;
use Sezzle\Payment\Model\Sezzle;
use Sezzle\Payment\Model\Tokenize;

/**
 * @api
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.2
 */
class Info extends \Magento\Sales\Block\Adminhtml\Order\View\Info
{

    private function getValue($key)
    {
        try {
            return $this->getOrder()->getPayment()->getAdditionalInformation($key);
        } catch (LocalizedException $e) {
            return null;
        }
    }

    /**
     * @return string|null
     */
    public function getSezzleAuthAmount()
    {
        try {
            return $this->getOrder()
                ->getBaseCurrency()
                ->formatTxt((float)$this->getValue(Sezzle::ADDITIONAL_INFORMATION_KEY_AUTH_AMOUNT));
        } catch (LocalizedException $e) {
            return null;
        }
    }

    /**
     * @return string|null
     */
    public function getSezzleRefundAmount()
    {
        try {
            return $this->getOrder()
                ->getBaseCurrency()
                ->formatTxt((float)$this->getValue(Sezzle::ADDITIONAL_INFORMATION_KEY_REFUND_AMOUNT));
        } catch (LocalizedException $e) {
            return null;
        }
    }

    /**
     * @return string|null
     */
    public function getSezzleCaptureAmount()
    {
        try {
            return $this->getOrder()
                ->getBaseCurrency()
                ->formatTxt((float)$this->getValue(Sezzle::ADDITIONAL_INFORMATION_KEY_CAPTURE_AMOUNT));
        } catch (LocalizedException $e) {
            return null;
        }
    }

    /**
     * @return string|null
     */
    public function getSezzleReleaseAmount()
    {
        try {
            return $this->getOrder()
                ->getBaseCurrency()
                ->formatTxt((float)$this->getValue(Sezzle::ADDITIONAL_INFORMATION_KEY_RELEASE_AMOUNT));
        } catch (LocalizedException $e) {
            return null;
        }
    }

    /**
     * @return string[]|null
     */
    public function getSezzleOrderReferenceID()
    {
        return $this->getValue(Sezzle::ADDITIONAL_INFORMATION_KEY_REFERENCE_ID);
    }

    /**
     * @return string[]|null
     */
    public function getSezzleCustomerUUID()
    {
        return $this->getValue(Tokenize::ATTR_SEZZLE_CUSTOMER_UUID);
    }

    /**
     * @return string|null
     */
    public function getSezzleAuthExpiry()
    {
        try {
            $authExpiry = $this->getValue(Sezzle::SEZZLE_AUTH_EXPIRY);
            return $authExpiry ? $this->formatDate(
                $authExpiry,
                \IntlDateFormatter::MEDIUM,
                true,
                $this->getTimezoneForStore($this->getOrder()->getStore())
            ) : null;
        } catch (LocalizedException $e) {
            return null;
        }
    }

    /**
     * @return string[]|null
     */
    public function getSezleCustomerUUIDExpiration()
    {
        return $this->getValue(Tokenize::ATTR_SEZZLE_CUSTOMER_UUID_EXPIRATION);
    }

    /**
     * @return string|null
     */
    public function getSezzlePaymentMethodCode()
    {
        try {
            return $this->getOrder()->getPayment()->getMethod();
        } catch (LocalizedException $e) {
            return null;
        }
    }

    /**
     * @return string[]|null
     */
    public function getSezzleOrderUUID()
    {
        return $this->getValue(Sezzle::ADDITIONAL_INFORMATION_KEY_ORDER_UUID);
    }
}
