<?php

namespace Sezzle\Sezzlepay\Model\GuestCart;

use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Sezzle\Sezzlepay\Api\CartManagementInterface;
use Sezzle\Sezzlepay\Api\GuestCartManagementInterface;

/**
 * Cart Management class for guest carts.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GuestCartManagement implements GuestCartManagementInterface
{

    /**
     * @var CartManagementInterface
     */
    protected $quoteManagement;

    /**
     * @var QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * @var CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * Initialize dependencies.
     *
     * @param CartManagementInterface $quoteManagement
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param CartRepositoryInterface $cartRepository
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        CartManagementInterface $quoteManagement,
        QuoteIdMaskFactory      $quoteIdMaskFactory,
        CartRepositoryInterface $cartRepository
    )
    {
        $this->quoteManagement = $quoteManagement;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->cartRepository = $cartRepository;
    }

    /**
     * @inheritDoc
     */
    public function placeOrder($cartId, PaymentInterface $paymentMethod = null): int
    {
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        $this->cartRepository->get($quoteIdMask->getQuoteId())
            ->setCheckoutMethod(CartManagementInterface::METHOD_GUEST);
        return $this->quoteManagement->placeOrder($quoteIdMask->getQuoteId(), $paymentMethod);
    }
}
