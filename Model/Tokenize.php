<?php

namespace Sezzle\Payment\Model;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Exception\State\InputMismatchException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Quote\Model\Quote;
use Sezzle\Payment\Api\Data\TokenizeCustomerInterface;
use Sezzle\Payment\Api\V2Interface;

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
     * @var V2Interface
     */
    private $v2;
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;
    /**
     * @var DateTime
     */
    private $dateTime;
    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var Quote
     */
    private $quote;

    public function __construct(
        CustomerSession $customerSession,
        DateTime $dateTime,
        CustomerRepositoryInterface $customerRepository,
        V2Interface $v2
    ) {
        $this->customerSession = $customerSession;
        $this->dateTime = $dateTime;
        $this->customerRepository = $customerRepository;
        $this->v2 = $v2;
    }

    /**
     * Saving tokenize record
     * @param Quote $quote
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws NotFoundException
     * @throws InputException
     * @throws InputMismatchException
     */
    public function saveTokenizeRecord($quote)
    {
        if (!($customerID = $this->customerSession->getCustomerId())) {
            throw new NoSuchEntityException(__('Unable to validate customer.'));
        }
        if ($this->quote == null) {
            $this->quote = $quote;
        }
        if (!$this->customerSession->getCustomerSezzleTokenStatus()
            || !($sezzleToken = $this->customerSession->getCustomerSezzleToken())
            || !$this->customerSession->getCustomerSezzleTokenExpiration()) {
            throw new NotFoundException(__('Tokenize record not found.'));
        }
        /** @var TokenizeCustomerInterface $tokenDetails */
        $url = $this->customerSession->getGetTokenDetailsLink();
        $tokenDetails = $this->v2->getTokenDetails($url, $sezzleToken);
        if (!$tokenDetails) {
            throw new NotFoundException(__('Unable to fetch token record from Sezzle.'));
        }
        $this->saveTokenizeRecordToCustomer($tokenDetails);
        $this->saveTokenizeRecordToQuote($tokenDetails);
        $this->customerSession->unsCustomerSezzleTokenStatus();
        $this->customerSession->unsCustomerSezzleToken();
        $this->customerSession->unsCustomerSezzleTokenExpiration();
        $this->customerSession->unsGetTokenDetailsLink();
    }

    /**
     * Saving tokenize record to Quote
     *
     * @param TokenizeCustomerInterface $tokenDetails
     * @throws LocalizedException
     */
    private function saveTokenizeRecordToQuote($tokenDetails)
    {
        $payment = $this->quote->getPayment();
        $payment->setAdditionalInformation(
            self::ATTR_SEZZLE_CUSTOMER_UUID,
            $tokenDetails->getUuid()
        );
        $payment->setAdditionalInformation(
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
        $customer->setCustomAttribute(
            Sezzle::ADDITIONAL_INFORMATION_KEY_CREATE_ORDER_LINK,
            $tokenDetails->getExpiration()
        );
        if (is_array($tokenDetails->getLinks())) {
            foreach ($tokenDetails->getLinks() as $link) {
                if ($link->getRel() == 'order') {
                    $customer->setCustomAttribute(
                        Sezzle::ADDITIONAL_INFORMATION_KEY_CREATE_ORDER_LINK,
                        $link
                    );
                }
            }
        }
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
        $sezzleCustomerUUIDExpirationTimestamp =  $this->dateTime->timestamp($sezzleCustomerUUIDExpiration->getValue());
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
