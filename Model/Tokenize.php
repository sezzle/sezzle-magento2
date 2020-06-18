<?php


namespace Sezzle\Payment\Model;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Exception\State\InputMismatchException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Quote\Model\Quote;
use Sezzle\Payment\Api\Data\TokenizeCustomerInterface;
use Sezzle\Payment\Api\V2Interface;
use Sezzle\Payment\Helper\Data;
use Magento\Customer\Model\Session as CustomerSession;

/**
 * Class Tokenize
 * @package Sezzle\Payment\Model
 */
class Tokenize
{
    const ATTR_SEZZLE_CUSTOMER_UUID = "sezzle_customer_uuid";
    const ATTR_SEZZLE_TOKEN_STATUS = "sezzle_tokenize_status";
    const ATTR_SEZZLE_CUSTOMER_UUID_EXPIRATION = "sezzle_customer_uuid_expiration";

    const STATUS_TOKEN_APPROVED = 'Approved';
    const STATUS_TOKEN_NOT_APPROVED = 'Not Approved';
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
     * @var Sezzle
     */
    private $sezzleModel;
    /**
     * @var V2Interface
     */
    private $v2;
    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * Tokenize constructor.
     * @param CustomerRepositoryInterface $customerRepository
     * @param Data $sezzleHelper
     * @param CustomerSession $customerSession
     * @param Sezzle $sezzleModel
     * @param DateTime $dateTime
     * @param V2Interface $v2
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        Data $sezzleHelper,
        CustomerSession $customerSession,
        Sezzle $sezzleModel,
        DateTime $dateTime,
        V2Interface $v2
    ) {
        $this->customerRepository = $customerRepository;
        $this->sezzleHelper = $sezzleHelper;
        $this->customerSession = $customerSession;
        $this->sezzleModel = $sezzleModel;
        $this->dateTime = $dateTime;
        $this->v2 = $v2;
    }

    /**
     * Saving tokenize record
     * @param $customerUUID
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws NotFoundException
     * @throws InputException
     * @throws InputMismatchException
     */
    public function saveTokenizeRecord($customerUUID)
    {
        if (!($customerID = $this->customerSession->getCustomerId())) {
            throw new NoSuchEntityException(__('Unable to validate customer.'));
        } elseif (!$this->customerSession->getCustomerSezzleTokenStatus()
        || !$this->customerSession->getCustomerSezzleToken()
        || !$this->customerSession->getCustomerSezzleTokenExpiration()) {
            throw new NotFoundException(__('Tokenize record not found.'));
        }
        if ($customerUUID && $sezzleToken = $this->customerSession->getCustomerSezzleToken()) {
            /** @var TokenizeCustomerInterface $tokenDetails */
            $tokenDetails = $this->v2->getTokenDetails($sezzleToken);
            if (!$tokenDetails) {
                throw new NotFoundException(__('Unable to fetch token record from Sezzle.'));
            }
            $this->saveTokenizeRecordToCustomer($tokenDetails);
            $this->saveTokenizeRecordToQuote($tokenDetails);
            $this->customerSession->unsCustomerSezzleTokenStatus();
            $this->customerSession->unsCustomerSezzleToken();
            $this->customerSession->unsCustomerSezzleTokenExpiration();
        }
    }

    /**
     * Saving tokenize record to Quote
     *
     * @param TokenizeCustomerInterface $tokenDetails
     * @throws LocalizedException
     */
    private function saveTokenizeRecordToQuote($tokenDetails)
    {
        $this->sezzleModel->setSezzleInformation(
            self::ATTR_SEZZLE_CUSTOMER_UUID,
            $tokenDetails->getUuid()
        );
        $this->sezzleModel->setSezzleInformation(
            self::ATTR_SEZZLE_CUSTOMER_UUID_EXPIRATION,
            $tokenDetails->getExpiration()
        );
    }

    /**
     * Saving tokenize record to Customer
     *
     * @param TokenizeCustomerInterface $tokenDetails
     * @throws InputException
     * @throws InputMismatchException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function saveTokenizeRecordToCustomer($tokenDetails)
    {
        $customer = $this->customerRepository->getById($this->customerSession->getCustomerId());
        $customer->setCustomAttribute(
            self::ATTR_SEZZLE_TOKEN_STATUS,
            true
        );
        $customer->setCustomAttribute(
            self::ATTR_SEZZLE_CUSTOMER_UUID,
            $tokenDetails->getUuid()
        );
        $customer->setCustomAttribute(
            self::ATTR_SEZZLE_CUSTOMER_UUID_EXPIRATION,
            $tokenDetails->getExpiration()
        );
        $this->customerRepository->save($customer);
    }

    /**
     * Validate Customer UUID
     *
     * @param Quote $quote
     * @return bool
     * @throws \Exception
     */
    public function isCustomerUUIDValid($quote)
    {
        if (!($sezzleCustomerUUID = $quote->getCustomer()
            ->getCustomAttribute(self::ATTR_SEZZLE_CUSTOMER_UUID))) {
            return false;
        } elseif (!($sezzleCustomerUUIDExpiration = $quote->getCustomer()
            ->getCustomAttribute(self::ATTR_SEZZLE_CUSTOMER_UUID_EXPIRATION))) {
            return false;
        }

        $currentTimestamp = $this->dateTime->timestamp('now');
        $sezzleCustomerUUIDExpirationTimestamp =  $this->dateTime->timestamp($sezzleCustomerUUIDExpiration);
        if ($currentTimestamp > $sezzleCustomerUUIDExpirationTimestamp) {
            $this->deleteCustomerTokenRecord($quote->getCustomerId());
            return false;
        }
        return true;
    }

    /**
     * @param int $customerID
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function deleteCustomerTokenRecord($customerID)
    {
        $customer = $this->customerRepository->getById($customerID);
        $customer->setCustomAttribute(self::ATTR_SEZZLE_CUSTOMER_UUID, null);
        $customer->setCustomAttribute(self::ATTR_SEZZLE_CUSTOMER_UUID_EXPIRATION, null);
    }
}
