<?php
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
    /**
     * @return string|null
     */
    public function getSezzleAuthAmount()
    {
        try {
            $authAmount = $this->getOrder()->getPayment()->getAdditionalInformation(Sezzle::ADDITIONAL_INFORMATION_KEY_AUTH_AMOUNT);
            return $this->getOrder()->getBaseCurrency()->formatTxt((float)$authAmount);
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
            $refundedAmount = $this->getOrder()->getPayment()->getAdditionalInformation(Sezzle::ADDITIONAL_INFORMATION_KEY_REFUND_AMOUNT);
            return $this->getOrder()->getBaseCurrency()->formatTxt((float)$refundedAmount);
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
            $capturedAmount = $this->getOrder()->getPayment()->getAdditionalInformation(Sezzle::ADDITIONAL_INFORMATION_KEY_CAPTURE_AMOUNT);
            return $this->getOrder()->getBaseCurrency()->formatTxt((float)$capturedAmount);
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
            $releasedAmount = $this->getOrder()->getPayment()->getAdditionalInformation(Sezzle::ADDITIONAL_INFORMATION_KEY_RELEASE_AMOUNT);
            return $this->getOrder()->getBaseCurrency()->formatTxt((float)$releasedAmount);
        } catch (LocalizedException $e) {
            return null;
        }
    }

    /**
     * @return string[]|null
     */
    public function getSezzleOrderReferenceID()
    {
        try {
            return $this->getOrder()->getPayment()->getAdditionalInformation(Sezzle::ADDITIONAL_INFORMATION_KEY_REFERENCE_ID);
        } catch (LocalizedException $e) {
            return null;
        }
    }

    /**
     * @return string[]|null
     */
    public function getSezzleCustomerUUID()
    {
        try {
            return $this->getOrder()->getPayment()->getAdditionalInformation(Tokenize::ATTR_SEZZLE_CUSTOMER_UUID);
        } catch (LocalizedException $e) {
            return null;
        }
    }

    /**
     * @return string|null
     */
    public function getSezzleAuthExpiry()
    {
        try {
            $authExpiry = $this->getOrder()->getPayment()->getAdditionalInformation(Sezzle::SEZZLE_AUTH_EXPIRY);
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
        try {
            return $this->getOrder()
                ->getPayment()
                ->getAdditionalInformation(Tokenize::ATTR_SEZZLE_CUSTOMER_UUID_EXPIRATION);
        } catch (LocalizedException $e) {
            return null;
        }
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
        try {
            return $this->getOrder()
                ->getPayment()
                ->getAdditionalInformation(Sezzle::ADDITIONAL_INFORMATION_KEY_ORDER_UUID);
        } catch (LocalizedException $e) {
            return null;
        }
    }
}
