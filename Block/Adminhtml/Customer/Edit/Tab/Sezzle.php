<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Block\Adminhtml\Customer\Edit\Tab;

use DateTimeZone;
use Exception;
use IntlDateFormatter;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Controller\RegistryConstants;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Framework\Registry;
use Magento\Ui\Component\Layout\Tabs\TabInterface;
use Sezzle\Sezzlepay\Model\Tokenize;

/**
 * Class Sezzle
 * @package Sezzle\Sezzlepay\Block\Adminhtml\Customer\Edit\Tab
 */
class Sezzle extends Template implements TabInterface
{

    /**
     * Template
     *
     * @var string
     */
    protected $_template = 'customer/token.phtml';
    /**
     * @var Registry
     */
    private $coreRegistry;
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * View constructor.
     * @param Context $context
     * @param Registry $registry
     * @param CustomerRepositoryInterface $customerRepository
     * @param array $data
     */
    public function __construct(
        Context                     $context,
        Registry                    $registry,
        CustomerRepositoryInterface $customerRepository,
        array                       $data = []
    )
    {
        $this->coreRegistry = $registry;
        $this->customerRepository = $customerRepository;
        parent::__construct($context, $data);
    }

    /**
     * Get Customer Id
     *
     * @return string|null
     */
    public function getCustomerId(): ?string
    {
        return $this->coreRegistry->registry(RegistryConstants::CURRENT_CUSTOMER_ID);
    }

    /**
     * Get Customer
     *
     * @return CustomerInterface|null
     */
    public function getCustomer(): ?CustomerInterface
    {
        try {
            return $this->customerRepository->getById($this->getCustomerId());
        } catch (NoSuchEntityException|LocalizedException $e) {
            return null;
        }
    }

    /**
     * Get Sezzle Token
     *
     * @return string|null
     */
    public function getToken(): ?string
    {
        if ($this->getCustomer()
            && $tokenAttr = $this->getCustomer()->getCustomAttribute(Tokenize::ATTR_SEZZLE_CUSTOMER_UUID)) {
            return $tokenAttr->getValue();
        }
        return null;
    }

    /**
     * Get Friendly Token Expiration
     *
     * @param $dateTime
     * @return string
     */
    private function getFriendlyTokenExpiration($dateTime): string
    {
        $values = explode('.', $dateTime);
        return $values[0];
    }

    /**
     * Get Sezzle Token Expiration
     *
     * @return string|null
     * @throws Exception
     */
    public function getTokenExpiration(): ?string
    {
        if ($this->getCustomer()
            && $tokenExpirationAttr = $this->getCustomer()
                ->getCustomAttribute(Tokenize::ATTR_SEZZLE_CUSTOMER_UUID_EXPIRATION)) {
            return $this->formatDate(
                $this->getFriendlyTokenExpiration($tokenExpirationAttr->getValue()),
                IntlDateFormatter::MEDIUM,
                true,
                new DateTimeZone('UTC')
            );
        }
        return null;
    }

    /**
     * Get Sezzle Token Status
     *
     * @return string|null
     */
    public function getTokenStatus(): ?string
    {
        if ($this->getCustomer()
            && $tokenStatusAttr = $this->getCustomer()->getCustomAttribute(Tokenize::ATTR_SEZZLE_TOKEN_STATUS)) {
            return $tokenStatusAttr->getValue() ? Tokenize::STATUS_TOKEN_APPROVED : Tokenize::STATUS_TOKEN_NOT_APPROVED;
        }
        return null;
    }

    /**
     * Get Tab Level
     *
     * @return Phrase
     */
    public function getTabLabel(): Phrase
    {
        return __('Sezzle');
    }

    /**
     * Get Tab Title
     *
     * @return Phrase
     */
    public function getTabTitle(): Phrase
    {
        return __('Sezzle');
    }

    /**
     * Can Show Tab or Not
     *
     * @return bool
     * @throws Exception
     */
    public function canShowTab(): bool
    {
        return $this->getToken() && $this->getTokenExpiration() && $this->getTokenStatus();
    }

    /**
     * Is Tab Hidden
     *
     * @return bool
     * @throws Exception
     */
    public function isHidden(): bool
    {
        return !($this->getToken() && $this->getTokenExpiration() && $this->getTokenStatus());
    }

    /**
     * Tab class getter
     *
     * @return string
     */
    public function getTabClass(): string
    {
        return '';
    }

    /**
     * Return URL link to Tab content
     *
     * @return string
     */
    public function getTabUrl(): string
    {
        return '';
    }

    /**
     * Tab should be loaded trough Ajax call
     *
     * @return bool
     */
    public function isAjaxLoaded(): bool
    {
        return false;
    }
}
