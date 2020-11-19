<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */
namespace Sezzle\Sezzlepay\Block\Adminhtml\Order\View;

use Magento\Backend\Block\Template\Context;
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Model\Metadata\ElementFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Sales\Helper\Admin;
use Magento\Sales\Model\Order\Address\Renderer;
use Sezzle\Sezzlepay\Model\Sezzle;
use Sezzle\Sezzlepay\Model\Tokenize;

/**
 * @api
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.2
 */
class Info extends \Magento\Sales\Block\Adminhtml\Order\View\Info
{

    /**
     * @var Sezzle
     */
    private $sezzleModel;

    /**
     * Info constructor.
     * @param Context $context
     * @param Registry $registry
     * @param Admin $adminHelper
     * @param GroupRepositoryInterface $groupRepository
     * @param CustomerMetadataInterface $metadata
     * @param ElementFactory $elementFactory
     * @param Renderer $addressRenderer
     * @param Sezzle $sezzleModel
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Admin $adminHelper,
        GroupRepositoryInterface $groupRepository,
        CustomerMetadataInterface $metadata,
        ElementFactory $elementFactory,
        Renderer $addressRenderer,
        Sezzle $sezzleModel,
        array $data = []
    ) {
        $this->sezzleModel = $sezzleModel;
        parent::__construct(
            $context,
            $registry,
            $adminHelper,
            $groupRepository,
            $metadata,
            $elementFactory,
            $addressRenderer,
            $data
        );
    }

    /**
     * Get value from payment additional info
     *
     * @param string $key
     * @return string[]|null
     */
    private function getValue($key)
    {
        try {
            return $this->getOrder()->getPayment()->getAdditionalInformation($key);
        } catch (LocalizedException $e) {
            return null;
        }
    }

    /**
     * Get Sezzle Order Type
     *
     * @return string[]|null
     */
    public function getSezzleOrderType()
    {
        return $this->getValue(Sezzle::SEZZLE_ORDER_TYPE);
    }

    /**
     * Get Sezzle Auth Amount
     *
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
     * Get Sezzle Refund Amount
     *
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
     * Get Sezzle Capture Amount
     *
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
     * Get Sezzle Release Amount
     *
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
     * Get Sezzle Order Reference ID
     *
     * @return string[]|null
     */
    public function getSezzleOrderReferenceID()
    {
        return $this->getValue(Sezzle::ADDITIONAL_INFORMATION_KEY_REFERENCE_ID);
    }

    /**
     * Get Sezzle Customer UUID
     *
     * @return string[]|null
     */
    public function getSezzleCustomerUUID()
    {
        return $this->getValue(Tokenize::ATTR_SEZZLE_CUSTOMER_UUID);
    }

    /**
     * Check if tokenize data are available
     *
     * @return bool
     */
    public function isTokenizedDataAvailable()
    {
        return $this->getSezzleCustomerUUID() && $this->getSezleCustomerUUIDExpiration();
    }

    /**
     * Get Sezzle Auth Expiry
     *
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
     * Check if auth is expired
     *
     * @return bool
     * @throws NoSuchEntityException|LocalizedException
     */
    public function isAuthExpired()
    {
        return !$this->sezzleModel->canInvoice($this->getOrder());
    }

    /**
     * Get Sezzle Capture Expiry
     *
     * @return string|null
     */
    public function getSezzleCaptureExpiry()
    {
        try {
            $captureExpiry = $this->getValue(Sezzle::SEZZLE_CAPTURE_EXPIRY);
            return $captureExpiry ? $this->formatDate(
                $captureExpiry,
                \IntlDateFormatter::MEDIUM,
                true,
                $this->getTimezoneForStore($this->getOrder()->getStore())
            ) : null;
        } catch (LocalizedException $e) {
            return null;
        }
    }

    /**
     * Get Capture Info
     *
     * @return string
     * @throws LocalizedException
     */
    public function getCaptureInfo()
    {
        return ($this->getOrder()->getGrandTotal() == $this->getOrder()->getTotalDue())
            ? '(Please capture before this)'
            : '(Captured)';
    }

    /**
     * @return string|null
     */
    public function getSezleCustomerUUIDExpiration()
    {
        try {
            $customerUUIExpirationTimestamp = $this->getValue(Tokenize::ATTR_SEZZLE_CUSTOMER_UUID_EXPIRATION);
            return $customerUUIExpirationTimestamp ? $this->formatDate(
                $customerUUIExpirationTimestamp,
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
    public function getSezzleOrderUUID()
    {
        return $this->getValue(Sezzle::ADDITIONAL_INFORMATION_KEY_ORIGINAL_ORDER_UUID);
    }
}
