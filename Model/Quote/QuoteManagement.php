<?php

namespace Sezzle\Sezzlepay\Model\Quote;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Sezzle\Sezzlepay\Api\CartManagementInterface;
use Magento\Quote\Api\CartManagementInterface as BaseCartManagementInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Sezzle\Sezzlepay\Gateway\Command\AuthorizeCommand;
use Sezzle\Sezzlepay\Gateway\Request\CustomerOrderRequestBuilder;
use Sezzle\Sezzlepay\Helper\Data;

/**
 * QuoteManagement
 */
class QuoteManagement implements CartManagementInterface
{

    /**
     * @var BaseCartManagementInterface
     */
    private $cartManagement;

    /**
     * @var CommandInterface
     */
    private $validateOrderCommand;

    /**
     * @var PaymentDataObjectFactory
     */
    private $paymentDataObjectFactory;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var CommandInterface
     */
    private $customerOrderCommand;

    /**
     * @var Data
     */
    private $helper;

    /**
     * QuoteManagement constructor
     * @param BaseCartManagementInterface $cartManagement
     * @param CommandInterface $validateOrderCommand
     * @param CommandInterface $customerOrderCommand
     * @param PaymentDataObjectFactory $paymentDataObjectFactory
     * @param CartRepositoryInterface $cartRepository
     * @param Data $helper
     */
    public function __construct(
        BaseCartManagementInterface $cartManagement,
        CommandInterface            $validateOrderCommand,
        CommandInterface            $customerOrderCommand,
        PaymentDataObjectFactory    $paymentDataObjectFactory,
        CartRepositoryInterface     $cartRepository,
        Data                        $helper
    )
    {
        $this->cartManagement = $cartManagement;
        $this->validateOrderCommand = $validateOrderCommand;
        $this->customerOrderCommand = $customerOrderCommand;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
        $this->cartRepository = $cartRepository;
        $this->helper = $helper;
    }

    /**
     * @inheritDoc
     */
    public function placeOrder(int $cartId, PaymentInterface $paymentMethod = null): int
    {
        $log = [
            'quote_id' => $cartId,
            'log_origin' => __METHOD__
        ];

        $quote = $this->cartRepository->getActive($cartId);

        // create customer order by customer_uuid
        $this->createCustomerOrder($quote);

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

        return $this->cartManagement->placeOrder($cartId, $paymentMethod);
    }

    /**
     * Creates customer order by Customer UUID
     *
     * @param CartInterface $quote
     * @return void
     * @throws LocalizedException
     */
    private function createCustomerOrder(CartInterface $quote): void
    {
        $payment = $quote->getPayment();

        $orderUUID = $payment->getAdditionalInformation(AuthorizeCommand::KEY_ORIGINAL_ORDER_UUID);
        $customerUUID = $payment->getAdditionalInformation(CustomerOrderRequestBuilder::KEY_CUSTOMER_UUID);
        if (!$orderUUID && $customerUUID) {
            try {
                $this->customerOrderCommand->execute([
                    'payment' => $this->paymentDataObjectFactory->create($quote->getPayment()),
                    'amount' => $quote->getBaseGrandTotal()
                ]);
            } catch (CommandException $e) {
                $this->helper->logSezzleActions([
                    'error' => $e->getMessage(),
                    'log_origin' => __METHOD__
                ]);

                throw new LocalizedException(__('Failed creating order at Sezzle.'));
            }
        }
    }
}
