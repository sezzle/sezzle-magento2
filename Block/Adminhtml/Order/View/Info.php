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
     * Get Sezzle Auth Amount
     *
     * @return string|null
     */
    public function getSezzleAuthAmount(): ?string
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
     * Get Sezzle Refund Amount
     *
     * @return string|null
     */
    public function getSezzleRefundAmount(): ?string
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
     * Get Sezzle Capture Amount
     *
     * @return string|null
     */
    public function getSezzleCaptureAmount(): ?string
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
     * Get Sezzle Release Amount
     *
     * @return string|null
     */
    public function getSezzleReleaseAmount(): ?string
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
     * Get Sezzle Order Reference ID
     *
     * @return mixed
     */
    public function getSezzleOrderReferenceID(): ?string
    {
        return $this->getValue(CustomerOrderRequestBuilder::KEY_REFERENCE_ID);
    }

    /**
     * Get Sezzle Customer UUID
     *
     * @return mixed
     */
    public function getSezzleCustomerUUID(): ?string
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
        return $this->getSezzleCustomerUUID() && $this->getSezleCustomerUUIDExpiration();
    }

    /**
     * Get Sezzle Auth Expiry
     *
     * @return string|null
     */
    public function getSezzleAuthExpiry(): ?string
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
    public function getSezzleCustomerUUIDExpiration(): ?string
    {
        try {
            $customerUUIExpirationTimestamp = $this->getValue(Tokenize::ATTR_SEZZLE_CUSTOMER_UUID_EXPIRATION);
            return $customerUUIExpirationTimestamp ? $this->formatDate(
                $customerUUIExpirationTimestamp,
                IntlDateFormatter::MEDIUM,
                true,
                $this->getTimezoneForStore($this->getOrder()->getStore())
            ) : null;
        } catch (LocalizedException $e) {
            return null;
        }
    }
}
