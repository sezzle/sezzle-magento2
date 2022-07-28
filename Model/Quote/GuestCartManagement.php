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
use Sezzle\Sezzlepay\Helper\Data;

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
     * @var Data
     */
    private $helper;

    /**
     * Initialize dependencies.
     *
     * @param CartManagementInterface $quoteManagement
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param CartRepositoryInterface $cartRepository
     * @param CommandInterface $validateOrderCommand
     * @param PaymentDataObjectFactory $paymentDataObjectFactory
     * @param Data $helper
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        CartManagementInterface  $quoteManagement,
        QuoteIdMaskFactory       $quoteIdMaskFactory,
        CartRepositoryInterface  $cartRepository,
        CommandInterface         $validateOrderCommand,
        PaymentDataObjectFactory $paymentDataObjectFactory,
        Data                     $helper
    )
    {
        $this->quoteManagement = $quoteManagement;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->cartRepository = $cartRepository;
        $this->validateOrderCommand = $validateOrderCommand;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
        $this->helper = $helper;
    }

    /**
     * @inheritDoc
     */
    public function placeOrder(string $cartId, PaymentInterface $paymentMethod = null): int
    {
        $log = [
            'masked_quote_id' => $cartId,
            'log_origin' => __METHOD__
        ];

        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        $quote = $this->cartRepository->get($quoteIdMask->getQuoteId())
            ->setCheckoutMethod(CartManagementInterface::METHOD_GUEST);

        $log['quote_id'] = $quote->getId();

        // validate Order
        try {
            $this->validateOrderCommand->execute(
                ['payment' => $this->paymentDataObjectFactory->create($quote->getPayment())]
            );
        } catch (CommandException $e) {
            $log['error'] = $e->getMessage();

            throw new LocalizedException(__('Failed order validation.'));
        } finally {
            $this->helper->logSezzleActions($log);
        }

        return $this->quoteManagement->placeOrder($quoteIdMask->getQuoteId(), $paymentMethod);
    }
}
