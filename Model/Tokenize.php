<?php


namespace Sezzle\Payment\Model;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Sezzle\Payment\Helper\Data;
use Magento\Customer\Model\Session as CustomerSession;

/**
 * Class Tokenize
 * @package Sezzle\Payment\Model
 */
class Tokenize
{
    const ATTR_SEZZLE_TOKEN = "sezzle_token";
    const ATTR_SEZZLE_TOKEN_STATUS = "sezzle_tokenize_status";
    const ATTR_SEZZLE_TOKEN_EXPIRATION = "sezzle_token_expiration";
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;
    /**
     * @var Data
     */
    private $sezzleHelper;
    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * Tokenize constructor.
     * @param CustomerRepositoryInterface $customerRepository
     * @param Data $sezzleHelper
     * @param CustomerSession $customerSession
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        Data $sezzleHelper,
        CustomerSession $customerSession
    ) {
        $this->customerRepository = $customerRepository;
        $this->sezzleHelper = $sezzleHelper;
        $this->customerSession = $customerSession;
    }

    /**
     * @param int $customerID
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function saveTokenizeRecord($customerID)
    {
        if ($customerID != $this->customerSession->getCustomerId()) {
            throw new NoSuchEntityException(__('Unable to validate customer.'));
        } elseif (!$this->customerSession->getCustomerSezzleTokenStatus()
        || !$this->customerSession->getCustomerSezzleToken()
        || !$this->customerSession->getCustomerSezzleTokenExpiration()) {
            throw new NotFoundException(__('Tokenize record not found.'));
        }
        $customer = $this->customerRepository->getById($customerID);
        $customer->setCustomAttribute(self::ATTR_SEZZLE_TOKEN_STATUS, $this->customerSession->getCustomerSezzleTokenStatus());
        $customer->setCustomAttribute(self::ATTR_SEZZLE_TOKEN, $this->customerSession->getCustomerSezzleToken());
        $customer->setCustomAttribute(
            self::ATTR_SEZZLE_TOKEN_EXPIRATION,
            $this->customerSession->getCustomerSezzleTokenExpiration()
        );
        $this->customerRepository->save($customer);
        $this->customerSession->unsCustomerSezzleTokenStatus();
        $this->customerSession->unsCustomerSezzleToken();
        $this->customerSession->unsCustomerSezzleTokenExpiration();
    }
}
