<?php

namespace Sezzle\Sezzlepay\Model\Quote;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Quote\Api\CartManagementInterface;
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
     * @var CommandInterface
     */
    private $validateOrderCommand;

    /**
     * @var PaymentDataObjectFactory
     */
    private $paymentDataObjectFactory;

    /**
     * Initialize dependencies.
     *
     * @param CartManagementInterface $quoteManagement
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param CartRepositoryInterface $cartRepository
     * @param CommandInterface $validateOrderCommand
     * @param PaymentDataObjectFactory $paymentDataObjectFactory
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        CartManagementInterface  $quoteManagement,
        QuoteIdMaskFactory       $quoteIdMaskFactory,
        CartRepositoryInterface  $cartRepository,
        CommandInterface         $validateOrderCommand,
        PaymentDataObjectFactory $paymentDataObjectFactory
    )
    {
        $this->quoteManagement = $quoteManagement;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->cartRepository = $cartRepository;
        $this->validateOrderCommand = $validateOrderCommand;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
    }

    /**
     * @inheritDoc
     */
    public function placeOrder(string $cartId, PaymentInterface $paymentMethod = null): int
    {
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        $quote = $this->cartRepository->get($quoteIdMask->getQuoteId())
            ->setCheckoutMethod(CartManagementInterface::METHOD_GUEST);

        // validate Order
        try {
            $this->validateOrderCommand->execute(
                ['payment' => $this->paymentDataObjectFactory->create($quote->getPayment())]
            );
        } catch (CommandException $e) {
            throw new LocalizedException(__('Failed order validation.'));
        }

        return $this->quoteManagement->placeOrder($quoteIdMask->getQuoteId(), $paymentMethod);
    }
}
