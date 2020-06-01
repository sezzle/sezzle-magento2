<?php


namespace Sezzle\Payment\Model;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Runner\Exception;
use Sezzle\Payment\Api\Data\SessionTokenizeInterface;
use Sezzle\Payment\Helper\Data;

/**
 * Class Tokenize
 * @package Sezzle\Payment\Model
 */
class Tokenize
{
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;
    /**
     * @var Data
     */
    private $sezzleHelper;
    /**
     * @var SessionTokenizeInterface
     */
    private $sessionTokenize;

    /**
     * Tokenize constructor.
     * @param CustomerRepositoryInterface $customerRepository
     * @param Data $sezzleHelper
     * @param SessionTokenizeInterface $sessionTokenize
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        Data $sezzleHelper,
        SessionTokenizeInterface $sessionTokenize
    ) {
        $this->customerRepository = $customerRepository;
        $this->sezzleHelper = $sezzleHelper;
        $this->sessionTokenize = $sessionTokenize;
    }

    /**
     * @param int $customerID
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function saveTokenizeRecord($customerID)
    {
        $customer = $this->customerRepository->getById($customerID);
        $sezzleToken = '1234';
        $sezzleTokenExpiration = '1234';
        $customer->setCustomAttribute('sezzle_tokenize_status', 'Approved');
        $customer->setCustomAttribute('sezzle_token', $sezzleToken);
        $customer->setCustomAttribute('sezzle_token_expiration', $sezzleTokenExpiration);
        $this->customerRepository->save($customer);
    }
}
