<?php

namespace Sezzle\Sezzlepay\Model\Quote;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Quote\Api\CartManagementInterface;
use Sezzle\Sezzlepay\Api\GuestCartManagementInterface;
use Sezzle\Sezzlepay\Gateway\Command\AuthorizeCommand;
use Sezzle\Sezzlepay\Gateway\Config\Config;
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
     * @var Config
     */
    private $config;

    /**
     * @var Curl
     */
    private $curl;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * Initialize dependencies.
     *
     * @param CartManagementInterface $quoteManagement
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param CartRepositoryInterface $cartRepository
     * @param CommandInterface $validateOrderCommand
     * @param PaymentDataObjectFactory $paymentDataObjectFactory
     * @param Data $helper
     * @param Config $config
     * @param Curl $curl
     * @param Json $jsonSerializer
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        CartManagementInterface  $quoteManagement,
        QuoteIdMaskFactory       $quoteIdMaskFactory,
        CartRepositoryInterface  $cartRepository,
        CommandInterface         $validateOrderCommand,
        PaymentDataObjectFactory $paymentDataObjectFactory,
        Data                     $helper,
        Config                   $config,
        Curl                     $curl,
        Json                     $jsonSerializer
    )
    {
        $this->quoteManagement = $quoteManagement;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->cartRepository = $cartRepository;
        $this->validateOrderCommand = $validateOrderCommand;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
        $this->helper = $helper;
        $this->config = $config;
        $this->curl = $curl;
        $this->jsonSerializer = $jsonSerializer;
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

        // Log payment method
        $payment = $quote->getPayment();
        $this->helper->logSezzleActions([
            'log_origin' => __METHOD__,
            'message' => 'Payment method check',
            'quote_id' => $quote->getId(),
            'payment_method' => $payment->getMethod(),
            'payment_additional_info' => $payment->getAdditionalInformation(),
            'shipping_method_before' => $quote->getShippingAddress()->getShippingMethod()
        ]);

        // Ensure payment method is set
        if (!$payment->getMethod()) {
            $payment->setMethod('sezzlepay');
            $this->helper->logSezzleActions([
                'log_origin' => __METHOD__,
                'message' => 'Payment method was missing - set to sezzlepay'
            ]);
        }

        // Set shipping method from Sezzle order
        $this->setShippingMethodFromSezzleOrder($quote);

        // Reload quote to ensure all changes are persisted
        $quote = $this->cartRepository->get($quote->getId());

        $this->helper->logSezzleActions([
            'log_origin' => __METHOD__,
            'message' => 'After setShippingMethodFromSezzleOrder',
            'quote_id' => $quote->getId(),
            'payment_method' => $quote->getPayment()->getMethod(),
            'shipping_method_after' => $quote->getShippingAddress()->getShippingMethod(),
            'billing_address' => [
                'firstname' => $quote->getBillingAddress()->getFirstname(),
                'lastname' => $quote->getBillingAddress()->getLastname(),
                'email' => $quote->getBillingAddress()->getEmail()
            ],
            'shipping_address' => [
                'firstname' => $quote->getShippingAddress()->getFirstname(),
                'lastname' => $quote->getShippingAddress()->getLastname(),
                'email' => $quote->getShippingAddress()->getEmail()
            ]
        ]);

        $log['shipping_address'] = $quote->getShippingAddress()->getData();
        $log['billing_address'] = $quote->getBillingAddress()->getData();
        $log['payment_method'] = $quote->getPayment()->getMethod();

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


    /**
     * Fetch Sezzle order and set shipping method on quote
     *
     * @param CartInterface $quote
     * @return void
     * @throws LocalizedException
     */
    private function setShippingMethodFromSezzleOrder(CartInterface $quote): void
    {
        $this->helper->logSezzleActions([
            'log_origin' => __METHOD__,
            'message' => 'ENTERED setShippingMethodFromSezzleOrder',
            'quote_id' => $quote->getId()
        ]);

        $payment = $quote->getPayment();
        $orderUUID = $payment->getAdditionalInformation(AuthorizeCommand::KEY_ORIGINAL_ORDER_UUID);

        $this->helper->logSezzleActions([
            'log_origin' => __METHOD__,
            'order_uuid' => $orderUUID,
            'payment_additional_info' => $payment->getAdditionalInformation()
        ]);

        if (!$orderUUID) {
            $this->helper->logSezzleActions([
                'log_origin' => __METHOD__,
                'error' => 'Order UUID not found in payment'
            ]);
            return;
        }

        try {
            $storeId = $quote->getStoreId();

            // Authenticate with Sezzle
            $authUrl = $this->config->getGatewayURL($storeId) . '/authentication';
            $authPayload = [
                'public_key' => $this->config->getPublicKey($storeId),
                'private_key' => $this->config->getPrivateKey($storeId)
            ];

            $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
            $this->curl->setOption(CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            $this->curl->post($authUrl, $this->jsonSerializer->serialize($authPayload));

            $authResponse = $this->curl->getBody();
            $authData = $this->jsonSerializer->unserialize($authResponse);

            if (!isset($authData['token'])) {
                throw new LocalizedException(__('Failed to authenticate with Sezzle'));
            }

            $token = $authData['token'];

            // Get order from Sezzle
            $orderUrl = $this->config->getGatewayURL($storeId) . '/order/' . $orderUUID;
            $this->curl->setOption(CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token
            ]);
            $this->curl->get($orderUrl);

            $orderResponse = $this->curl->getBody();
            $orderData = $this->jsonSerializer->unserialize($orderResponse);

            $this->helper->logSezzleActions([
                'log_origin' => __METHOD__,
                'order_uuid' => $orderUUID,
                'order_data' => $orderData
            ]);

            // Set customer information and addresses
            if (isset($orderData['customer'])) {
                $customer = $orderData['customer'];
                $shippingAddress = $quote->getShippingAddress();
                $billingAddress = $quote->getBillingAddress();

                // Set customer email
                if (isset($customer['email'])) {
                    $quote->setCustomerEmail($customer['email']);
                }

                // Set billing address
                if (isset($customer['billing_address'])) {
                    $sezzleBilling = $customer['billing_address'];
                    $billingAddress->setFirstname($customer['first_name'] ?? '')
                        ->setLastname($customer['last_name'] ?? '')
                        ->setStreet([
                            $sezzleBilling['street'] ?? '',
                            $sezzleBilling['street2'] ?? ''
                        ])
                        ->setCity($sezzleBilling['city'] ?? '')
                        ->setRegion($sezzleBilling['state'] ?? '')
                        ->setPostcode($sezzleBilling['postal_code'] ?? '')
                        ->setCountryId($sezzleBilling['country_code'] ?? '')
                        ->setTelephone($customer['phone'] ?? '')
                        ->setEmail($customer['email'] ?? '');

                    $this->helper->logSezzleActions([
                        'log_origin' => __METHOD__,
                        'message' => 'Billing address set from Sezzle order'
                    ]);
                }

                // Confirm/Set shipping address (should already be set by updateOrderWithAddress)
                if (isset($customer['shipping_address'])) {
                    $sezzleShipping = $customer['shipping_address'];

                    // Only set if fields are missing
                    if (!$shippingAddress->getFirstname()) {
                        $shippingAddress->setFirstname($customer['first_name'] ?? '');
                    }
                    if (!$shippingAddress->getLastname()) {
                        $shippingAddress->setLastname($customer['last_name'] ?? '');
                    }
                    if (!$shippingAddress->getEmail()) {
                        $shippingAddress->setEmail($customer['email'] ?? '');
                    }

                    $this->helper->logSezzleActions([
                        'log_origin' => __METHOD__,
                        'message' => 'Shipping address confirmed from Sezzle order'
                    ]);
                }
            }

            // Extract shipping method ID
            if (isset($orderData['shipping_method']['id'])) {
                $shippingMethodCode = $orderData['shipping_method']['id'];
                $shippingAddress = $quote->getShippingAddress();

                // Log current address state
                $this->helper->logSezzleActions([
                    'log_origin' => __METHOD__,
                    'order_uuid' => $orderUUID,
                    'shipping_address_before' => [
                        'firstname' => $shippingAddress->getFirstname(),
                        'lastname' => $shippingAddress->getLastname(),
                        'street' => $shippingAddress->getStreet(),
                        'city' => $shippingAddress->getCity(),
                        'postcode' => $shippingAddress->getPostcode(),
                        'country_id' => $shippingAddress->getCountryId(),
                        'region' => $shippingAddress->getRegion(),
                        'telephone' => $shippingAddress->getTelephone()
                    ]
                ]);

                // Set the shipping method
                $shippingAddress->setShippingMethod($shippingMethodCode)
                    ->setCollectShippingRates(true);

                // Recalculate totals
                $quote->setTotalsCollectedFlag(false);
                $quote->collectTotals();
                $this->cartRepository->save($quote);

                $this->helper->logSezzleActions([
                    'log_origin' => __METHOD__,
                    'order_uuid' => $orderUUID,
                    'shipping_method_set' => $shippingMethodCode,
                    'grand_total' => $quote->getGrandTotal(),
                    'shipping_address_after' => [
                        'firstname' => $shippingAddress->getFirstname(),
                        'lastname' => $shippingAddress->getLastname(),
                        'street' => $shippingAddress->getStreet(),
                        'city' => $shippingAddress->getCity(),
                        'postcode' => $shippingAddress->getPostcode(),
                        'country_id' => $shippingAddress->getCountryId(),
                        'telephone' => $shippingAddress->getTelephone()
                    ]
                ]);
            } else {
                $this->helper->logSezzleActions([
                    'log_origin' => __METHOD__,
                    'order_uuid' => $orderUUID,
                    'warning' => 'No shipping method found in Sezzle order'
                ]);
            }
        } catch (\Exception $e) {
            $this->helper->logSezzleActions([
                'log_origin' => __METHOD__,
                'order_uuid' => $orderUUID,
                'error' => $e->getMessage()
            ]);
            // Don't throw exception, just log - allow order to continue
        }
    }
}
