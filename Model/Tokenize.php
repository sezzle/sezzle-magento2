<?php

namespace Sezzle\Sezzlepay\Model;

use Exception;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Api\AttributeInterfaceFactory;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Exception\State\InputMismatchException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Quote\Model\Quote;
use Sezzle\Sezzlepay\Api\Data\TokenizeCustomerInterface;
use Sezzle\Sezzlepay\Api\V2Interface;
use Sezzle\Sezzlepay\Helper\Data;

/**
 * Class Tokenize
 * @package Sezzle\Sezzlepay\Model
 */
class Tokenize
{
    const ATTR_SEZZLE_CUSTOMER_UUID = "sezzle_customer_uuid";
    const ATTR_SEZZLE_TOKEN_STATUS = "sezzle_tokenize_status";
    const ATTR_SEZZLE_CUSTOMER_UUID_EXPIRATION = "sezzle_customer_uuid_expiry";

    const KEY_GET_TOKEN_DETAILS_LINK = 'sezzle_token_link';

    const KEY_CREATE_ORDER_LINK = 'sezzle_create_order_link';
    const KEY_GET_CUSTOMER_LINK = 'sezzle_get_customer_link';

    const STATUS_TOKEN_APPROVED = 'Approved';
    const STATUS_TOKEN_NOT_APPROVED = 'Not Approved';

    public $sezzleCustomerAttributes = [
        Tokenize::ATTR_SEZZLE_CUSTOMER_UUID => [
            'input' => 'text',
            'label' => 'Sezzle Tokenize Status',
        ],
        Tokenize::ATTR_SEZZLE_TOKEN_STATUS => [
            'input' => 'boolean',
            'label' => 'Sezzle Customer UUID'
        ],
        Tokenize::ATTR_SEZZLE_CUSTOMER_UUID_EXPIRATION => [
            'input' => 'text',
            'label' => 'Sezzle Customer UUID Expiration'
        ],
        self::KEY_CREATE_ORDER_LINK => [
            'input' => 'text',
            'label' => 'Sezzle Order Create Link By Customer UUID',
        ],
        self::KEY_GET_CUSTOMER_LINK => [
            'input' => 'text',
            'label' => 'Sezzle Get Customer Link By Customer UUID',
        ]
    ];
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
    /**
     * @var Data
     */
    private $helper;
    /**
     * @var AttributeInterfaceFactory
     */
    private $attributeFactory;

    /**
     * Tokenize constructor.
     * @param CustomerSession $customerSession
     * @param DateTime $dateTime
     * @param CustomerRepositoryInterface $customerRepository
     * @param AttributeInterfaceFactory $attributeFactory
     * @param Data $helper
     * @param V2Interface $v2
     */
    public function __construct(
        CustomerSession             $customerSession,
        DateTime                    $dateTime,
        CustomerRepositoryInterface $customerRepository,
        AttributeInterfaceFactory   $attributeFactory,
        Data                        $helper,
        V2Interface                 $v2
    )
    {
        $this->customerSession = $customerSession;
        $this->dateTime = $dateTime;
        $this->customerRepository = $customerRepository;
        $this->attributeFactory = $attributeFactory;
        $this->helper = $helper;
        $this->v2 = $v2;
    }

    /**
     * Saving tokenize record
     * @param Quote $quote
     */
    public function saveTokenizeRecord(Quote $quote)
    {
        try {
            if ($this->quote == null) {
                $this->quote = $quote;
            }

            if (!$this->customerSession->getCustomerSezzleTokenStatus()
                || !($sezzleToken = $this->customerSession->getCustomerSezzleToken())
                || !$this->customerSession->getCustomerSezzleTokenExpiration()) {
                throw new NotFoundException(__('Tokenize record not found.'));
            }

            $uri = $this->customerSession->getGetTokenDetailsLink();

            $tokenDetails = $this->v2->getTokenDetails($uri, $sezzleToken, $quote->getStoreId());
            if ($this->customerSession->getCustomerId()) {
                $this->saveTokenizeRecordToCustomer($tokenDetails);
            }
            $this->saveTokenizeRecordToQuote($tokenDetails);
            $this->customerSession->unsCustomerSezzleTokenStatus();
            $this->customerSession->unsCustomerSezzleToken();
            $this->customerSession->unsCustomerSezzleTokenExpiration();
            $this->customerSession->unsGetTokenDetailsLink();
        } catch (LocalizedException $e) {
            $this->helper->logSezzleActions("Sezzle Tokenize Record Save Error");
            $this->helper->logSezzleActions($e->getMessage());
        }
    }

    /**
     * Saving tokenize record to Quote
     *
     * @param TokenizeCustomerInterface $tokenDetails
     * @throws LocalizedException
     */
    private function saveTokenizeRecordToQuote(TokenizeCustomerInterface $tokenDetails)
    {
        $payment = $this->quote->getPayment();
        $additionalInfo = [
            self::ATTR_SEZZLE_CUSTOMER_UUID => $tokenDetails->getUuid(),
            self::ATTR_SEZZLE_CUSTOMER_UUID_EXPIRATION => $tokenDetails->getExpiration()
        ];
        $this->helper->logSezzleActions("Tokenize records to be engaged with Quote");
        $this->helper->logSezzleActions($additionalInfo);
        $payment->setAdditionalInformation(
            array_merge(
                $payment->getAdditionalInformation(),
                $additionalInfo
            )
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
    private function saveTokenizeRecordToCustomer(TokenizeCustomerInterface $tokenDetails)
    {
        $customer = $this->customerRepository->getById($this->customerSession->getCustomerId());
        $tokenStatusAttribute = $this->attributeFactory->create()
            ->setAttributeCode(self::ATTR_SEZZLE_TOKEN_STATUS)
            ->setValue(true);
        $customerUUIDAttribute = $this->attributeFactory->create()
            ->setAttributeCode(self::ATTR_SEZZLE_CUSTOMER_UUID)
            ->setValue($tokenDetails->getUuid());
        $customerUUIDExpirationAttribute = $this->attributeFactory->create()
            ->setAttributeCode(self::ATTR_SEZZLE_CUSTOMER_UUID_EXPIRATION)
            ->setValue($tokenDetails->getExpiration());
        $tokenAttributes = [
            $tokenStatusAttribute,
            $customerUUIDAttribute,
            $customerUUIDExpirationAttribute
        ];
        if (is_array($tokenDetails->getLinks())) {
            foreach ($tokenDetails->getLinks() as $link) {
                switch ($link->getRel()) {
                    case 'order':
                        if ($link->getMethod() == 'POST') {
                            $createOrderLinkAttribute = [
                                $this->attributeFactory->create()
                                    ->setAttributeCode(self::KEY_CREATE_ORDER_LINK)
                                    ->setValue($link->getHref())
                            ];
                            $tokenAttributes = array_merge($tokenAttributes, $createOrderLinkAttribute);
                        }
                        break;
                    case 'self':
                        if ($link->getMethod() == 'GET') {
                            $getCustomerLinkAttribute = [
                                $this->attributeFactory->create()
                                    ->setAttributeCode(self::KEY_GET_CUSTOMER_LINK)
                                    ->setValue($link->getHref())
                            ];
                            $tokenAttributes = array_merge($tokenAttributes, $getCustomerLinkAttribute);
                        }
                        break;
                    default:
                        break;
                }
            }
        }
        $this->helper->logSezzleActions("Tokenize records to be engaged with Customer");
        $this->helper->logSezzleActions($tokenAttributes);
        $customer->setCustomAttributes($tokenAttributes);
        $this->customerRepository->save($customer);
    }

    /**
     * Validate Customer UUID
     *
     * @param Quote $quote
     * @return bool
     * @throws Exception
     */
    public function isCustomerUUIDValid(Quote $quote): bool
    {
        if (!($customerUUID = $quote->getCustomer()
            ->getCustomAttribute(self::ATTR_SEZZLE_CUSTOMER_UUID))) {
            return false;
        } elseif (!($customerUUIDExpiration = $quote->getCustomer()
            ->getCustomAttribute(self::ATTR_SEZZLE_CUSTOMER_UUID_EXPIRATION))) {
            return false;
        }

        $url = $quote->getCustomer()->getCustomAttribute(self::KEY_GET_CUSTOMER_LINK)->getValue();
        $customer = $this->v2->getCustomer($url, $customerUUID->getValue(), $quote->getStoreId());
        $currentTimestamp = $this->dateTime->timestamp('now');
        $customerUUIDExpirationTimestamp = $this->dateTime->timestamp($customerUUIDExpiration->getValue());
        if (!$customer->getFirstName() || ($currentTimestamp > $customerUUIDExpirationTimestamp)) {
            $this->helper->logSezzleActions("Customer UUID is not valid. Deleting record now.");
            $this->deleteCustomerTokenRecord($quote->getCustomerId());
            $this->helper->logSezzleActions("Tokenize record deleted.");
            return false;
        }
        $this->helper->logSezzleActions("Customer UUID is valid");
        return true;
    }

    /**
     * Delete Customer Token Record
     *
     * @param int $customerID
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function deleteCustomerTokenRecord(int $customerID)
    {
        $customer = $this->customerRepository->getById($customerID);
        foreach ($this->sezzleCustomerAttributes as $attributeCode => $value) {
            $customer->setCustomAttribute($attributeCode, null);
        }
        $this->customerRepository->save($customer);
    }
}
