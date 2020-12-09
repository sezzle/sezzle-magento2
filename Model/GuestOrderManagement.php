<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model;

use Magento\Checkout\Api\GuestPaymentInformationManagementInterface;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Sezzle\Sezzlepay\Api\GuestOrderManagementInterface;
use Sezzle\Sezzlepay\Model\Order\SaveHandler;

class GuestOrderManagement implements GuestOrderManagementInterface
{

    /**
     * @var CartRepositoryInterface
     */
    protected $cartRepository;
    /**
     * @var
     */
    private $saveHandler;
    /**
     * @var QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;
    /**
     * @var GuestPaymentInformationManagementInterface
     */
    private $paymentInformationManagement;

    /**
     * Payment constructor.
     * @param CartRepositoryInterface $cartRepository
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param GuestPaymentInformationManagementInterface $paymentInformationManagement
     */
    public function __construct(
        CartRepositoryInterface $cartRepository,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        GuestPaymentInformationManagementInterface $paymentInformationManagement
    ) {
        $this->cartRepository = $cartRepository;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->paymentInformationManagement = $paymentInformationManagement;
    }

    /**
     * @inheritDoc
     */
    public function createCheckout(
        $cartId,
        $email,
        $createSezzleCheckout,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    ) {
        try {
            $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
            /** @var Quote $quote */
            $quote = $this->cartRepository->getActive($quoteIdMask->getQuoteId());
            if (!$quote) {
                throw new NotFoundException(__("Cart ID is invalid."));
            }
            $this->paymentInformationManagement->savePaymentInformation(
                $cartId,
                $email,
                $paymentMethod,
                $billingAddress
            );
            $quote->setCustomerId(null)
                ->setCustomerEmail($email)
                ->setCustomerIsGuest(true)
                ->setCustomerGroupId(GroupInterface::NOT_LOGGED_IN_ID);
            return $this->getSaveHandler()->createCheckout($quote, $createSezzleCheckout);
        } catch (NoSuchEntityException $e) {
            throw new CouldNotSaveException(
                __($e->getMessage()),
                $e
            );
        } catch (NotFoundException $e) {
            throw new CouldNotSaveException(
                __($e->getMessage()),
                $e
            );
        } catch (LocalizedException $e) {
            throw new CouldNotSaveException(
                __($e->getMessage()),
                $e
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function placeOrder($cartId)
    {
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        /** @var Quote $quote */
        $quote = $this->cartRepository->getActive($quoteIdMask->getQuoteId());
        if (!$quote) {
            throw new NotFoundException(__("Cart ID is invalid."));
        }
        try {
            return $this->getSaveHandler()->save($quote);
        } catch (CouldNotSaveException $e) {
            throw new CouldNotSaveException(
                __($e->getMessage()),
                $e
            );
        } catch (NoSuchEntityException $e) {
            throw new CouldNotSaveException(
                __($e->getMessage()),
                $e
            );
        }
    }

    /**
     * Get Save Handler
     *
     * @return SaveHandler
     */
    private function getSaveHandler()
    {
        if (!$this->saveHandler) {
            $this->saveHandler = ObjectManager::getInstance()->get(SaveHandler::class);
        }
        return $this->saveHandler;
    }
}
