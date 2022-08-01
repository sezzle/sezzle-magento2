<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Block\Adminhtml\Order\View;

use IntlDateFormatter;
use Magento\Backend\Block\Template\Context;
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Model\Metadata\ElementFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Payment\Gateway\Validator\ValidatorInterface;
use Magento\Sales\Helper\Admin;
use Magento\Sales\Model\Order\Address\Renderer;
use Sezzle\Sezzlepay\Gateway\Command\AuthorizeCommand;
use Sezzle\Sezzlepay\Gateway\Request\CustomerOrderRequestBuilder;
use Sezzle\Sezzlepay\Gateway\Response\CaptureHandler;
use Sezzle\Sezzlepay\Gateway\Response\RefundHandler;
use Sezzle\Sezzlepay\Gateway\Response\ReleaseHandler;
use Sezzle\Sezzlepay\Gateway\Validator\AuthorizationValidator;
use Sezzle\Sezzlepay\Model\Tokenize;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Sezzle\Sezzlepay\Model\Ui\ConfigProvider;

/**
 * @api
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.2
 */
class Info extends \Magento\Sales\Block\Adminhtml\Order\View\Info
{

    /**
     * @var ValidatorInterface
     */
    private $authValidator;

    /**
     * @var PaymentDataObjectFactory
     */
    private $paymentDataObjectFactory;

    /**
     * Info constructor.
     * @param Context $context
     * @param Registry $registry
     * @param Admin $adminHelper
     * @param GroupRepositoryInterface $groupRepository
     * @param CustomerMetadataInterface $metadata
     * @param ElementFactory $elementFactory
     * @param Renderer $addressRenderer
     * @param ValidatorInterface $authValidator
     * @param PaymentDataObjectFactory $paymentDataObjectFactory
     * @param array $data
     */
    public function __construct(
        Context                   $context,
        Registry                  $registry,
        Admin                     $adminHelper,
        GroupRepositoryInterface  $groupRepository,
        CustomerMetadataInterface $metadata,
        ElementFactory            $elementFactory,
        Renderer                  $addressRenderer,
        ValidatorInterface        $authValidator,
        PaymentDataObjectFactory  $paymentDataObjectFactory,
        array                     $data = []
    )
    {
        $this->authValidator = $authValidator;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
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
     * Check if current order is Sezzle Order
     *
     * @return bool
     * @throws LocalizedException
     */
    public function isSezzleOrder(): bool
    {
        return $this->getOrder()->getPayment()->getMethod() == ConfigProvider::CODE;
    }

    /**
     * Get value from payment additional info
     *
     * @param string $key
     * @return array|null|mixed
     */
    private function getValue(string $key): ?string
    {
        try {
            return $this->getOrder()->getPayment()->getAdditionalInformation($key);
        } catch (LocalizedException $e) {
            return null;
        }
    }

    /**
     * Get Authorized Amount
     *
     * @return string|null
     */
    public function getAuthorizedAmount(): ?string
    {
        try {
            return $this->getOrder()
                ->getBaseCurrency()
                ->formatTxt((float)$this->getValue(AuthorizeCommand::KEY_AUTH_AMOUNT));
        } catch (LocalizedException $e) {
            return null;
        }
    }

    /**
     * Get Refunded Amount
     *
     * @return string|null
     */
    public function getRefundedAmount(): ?string
    {
        try {
            return $this->getOrder()
                ->getBaseCurrency()
                ->formatTxt((float)$this->getValue(RefundHandler::KEY_REFUND_AMOUNT));
        } catch (LocalizedException $e) {
            return null;
        }
    }

    /**
     * Get Captured Amount
     *
     * @return string|null
     */
    public function getCapturedAmount(): ?string
    {
        try {
            return $this->getOrder()
                ->getBaseCurrency()
                ->formatTxt((float)$this->getValue(CaptureHandler::KEY_CAPTURE_AMOUNT));
        } catch (LocalizedException $e) {
            return null;
        }
    }

    /**
     * Get Released Amount
     *
     * @return string|null
     */
    public function getReleasedAmount(): ?string
    {
        try {
            return $this->getOrder()
                ->getBaseCurrency()
                ->formatTxt((float)$this->getValue(ReleaseHandler::KEY_RELEASE_AMOUNT));
        } catch (LocalizedException $e) {
            return null;
        }
    }

    /**
     * Get Order Reference ID
     *
     * @return mixed
     */
    public function getOrderReferenceID(): ?string
    {
        return $this->getValue(CustomerOrderRequestBuilder::KEY_REFERENCE_ID);
    }

    /**
     * Get Customer UUID
     *
     * @return mixed
     */
    public function getCustomerUUID(): ?string
    {
        return $this->getValue(CustomerOrderRequestBuilder::KEY_CUSTOMER_UUID);
    }

    /**
     * Check if tokenize data are available
     *
     * @return bool
     */
    public function isTokenizedDataAvailable(): bool
    {
        return $this->getCustomerUUID() && $this->getCustomerUUIDExpiration();
    }

    /**
     * Get Auth Expiry
     *
     * @return string|null
     */
    public function getAuthExpiry(): ?string
    {
        try {
            $authExpiry = $this->getValue(AuthorizationValidator::KEY_AUTH_EXPIRY);
            return $authExpiry ? $this->formatDate(
                $authExpiry,
                IntlDateFormatter::MEDIUM,
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
    public function isAuthExpired(): bool
    {
        $authValidatorResult = $this->authValidator->validate(
            ['payment' => $this->paymentDataObjectFactory->create($this->getOrder()->getPayment())]
        );
        return !$authValidatorResult->isValid();
    }

    /**
     * Get Capture Info
     *
     * @return string
     * @throws LocalizedException
     */
    public function getCaptureInfo(): string
    {
        return ($this->getOrder()->getGrandTotal() == $this->getOrder()->getTotalDue())
            ? '(Please capture before this)'
            : '(Captured)';
    }

    /**
     * @return string|null
     */
    public function getCustomerUUIDExpiration(): ?string
    {
        try {
            $customerUUIDExpirationTimestamp = $this->getValue(Tokenize::ATTR_SEZZLE_CUSTOMER_UUID_EXPIRATION);
            return $customerUUIDExpirationTimestamp ? $this->formatDate(
                $customerUUIDExpirationTimestamp,
                IntlDateFormatter::MEDIUM,
                true,
                $this->getTimezoneForStore($this->getOrder()->getStore())
            ) : null;
        } catch (LocalizedException $e) {
            return null;
        }
    }
}
