<?php

namespace Sezzle\Sezzlepay\Model\Quote;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Sezzle\Sezzlepay\Api\CartManagementInterface;
use Magento\Quote\Api\CartManagementInterface as BaseCartManagementInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Sezzle\Sezzlepay\Gateway\Command\AuthorizeCommand;
use Sezzle\Sezzlepay\Gateway\Config\Config;
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
     * QuoteManagement constructor
     * @param BaseCartManagementInterface $cartManagement
     * @param CommandInterface $validateOrderCommand
     * @param CommandInterface $customerOrderCommand
     * @param PaymentDataObjectFactory $paymentDataObjectFactory
     * @param CartRepositoryInterface $cartRepository
     * @param Data $helper
     * @param Config $config
     * @param Curl $curl
     * @param Json $jsonSerializer
     */
    public function __construct(
        BaseCartManagementInterface $cartManagement,
        CommandInterface            $validateOrderCommand,
        CommandInterface            $customerOrderCommand,
        PaymentDataObjectFactory    $paymentDataObjectFactory,
        CartRepositoryInterface     $cartRepository,
        Data                        $helper,
        Config                      $config,
        Curl                        $curl,
        Json                        $jsonSerializer
    )
    {
        $this->cartManagement = $cartManagement;
        $this->validateOrderCommand = $validateOrderCommand;
        $this->customerOrderCommand = $customerOrderCommand;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
        $this->cartRepository = $cartRepository;
        $this->helper = $helper;
        $this->config = $config;
        $this->curl = $curl;
        $this->jsonSerializer = $jsonSerializer;
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

        // Log payment method
        $payment = $quote->getPayment();
        $this->helper->logSezzleActions([
            'log_origin' => __METHOD__,
            'message' => 'Payment method check',
            'quote_id' => $quote->getId(),
            'payment_method' => $payment->getMethod(),
            'payment_additional_info' => $payment->getAdditionalInformation()
        ]);

        // Ensure payment method is set
        if (!$payment->getMethod()) {
            $payment->setMethod('sezzlepay');
            $this->helper->logSezzleActions([
                'log_origin' => __METHOD__,
                'message' => 'Payment method was missing - set to sezzlepay'
            ]);
        }

        // Set shipping method and addresses from Sezzle order
        $this->setShippingMethodFromSezzleOrder($quote);

        // Reload quote to ensure all changes are persisted
        $quote = $this->cartRepository->get($quote->getId());

        $this->helper->logSezzleActions([
            'log_origin' => __METHOD__,
            'message' => 'After setShippingMethodFromSezzleOrder',
            'quote_id' => $quote->getId(),
            'payment_method' => $quote->getPayment()->getMethod(),
            'shipping_method' => $quote->getShippingAddress()->getShippingMethod()
        ]);

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

                $shippingAddress->setCollectShippingRates(true);
                $quote->setTotalsCollectedFlag(false);
                $shippingAddress->requestShippingRates(); // This actually collects the rates

                // STEP 2: Verify the shipping method exists in available rates
                $availableRates = $shippingAddress->getAllShippingRates();
                $methodFound = false;
                foreach ($availableRates as $rate) {
                    $rateCode = $rate->getCarrier() . '_' . $rate->getMethod();
                    if ($rateCode === $shippingMethodCode) {
                        $methodFound = true;
                        $this->helper->logSezzleActions([
                            'log_origin' => __METHOD__,
                            'message' => 'Shipping method found in available rates',
                            'method_code' => $shippingMethodCode,
                            'rate_price' => $rate->getPrice()
                        ]);
                        break;
                    }
                }

                if (!$methodFound) {
                    $this->helper->logSezzleActions([
                        'log_origin' => __METHOD__,
                        'warning' => 'Shipping method not found in available rates',
                        'requested_method' => $shippingMethodCode,
                        'available_rates' => array_map(function($rate) {
                            return $rate->getCarrier() . '_' . $rate->getMethod();
                        }, $availableRates)
                    ]);
                }

                // STEP 3: Now set the shipping method
                $shippingAddress->setShippingMethod($shippingMethodCode);
                $shippingAddress->save();

                // STEP 4: Recalculate totals
                $quote->setTotalsCollectedFlag(false);
                $quote->collectTotals();

                // Verify the method is still set
                $currentMethod = $shippingAddress->getShippingMethod();

                $this->helper->logSezzleActions([
                    'log_origin' => __METHOD__,
                    'current_method' => $currentMethod,
                    'shipping_method_code' => $shippingMethodCode
                ]);

                // If it got reset, force it again
                if ($currentMethod !== $shippingMethodCode) {
                    $this->helper->logSezzleActions([
                        'log_origin' => __METHOD__,
                        'warning' => 'Shipping method was reset by collectTotals',
                        'expected' => $shippingMethodCode,
                        'actual' => $currentMethod,
                        'forcing_again' => true
                    ]);

                    // Force it again
                    $shippingAddress->setShippingMethod($shippingMethodCode);
                }

                $this->helper->logSezzleActions([
                    'log_origin' => __METHOD__,
                    'order_uuid' => $orderUUID,
                    'shipping_method_set' => $shippingMethodCode,
                    'shipping_method' => $shippingAddress->getShippingMethod(),
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
