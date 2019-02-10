<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 * @license     https://www.sezzle.com/LICENSE.txt
 */

namespace Sezzle\Sezzlepay\Model\Gateway;


use Sezzle\Sezzlepay\Model\Config\Container\SezzleApiConfigInterface;

/**
 * Class Transaction
 * @package Sezzle\Sezzlepay\Model\Gateway
 */
class Transaction
{
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    private $orderFactory;
    /**
     * @var SezzleApiConfigInterface
     */
    private $sezzleApiConfig;
    /**
     * @var \Sezzle\Sezzlepay\Model\Api\ProcessorInterface
     */
    private $sezzleApiProcessor;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;
    /**
     * @var \Magento\Sales\Api\Data\OrderInterface
     */
    private $orderInterface;

    /**
     * Transaction constructor.
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Sezzle\Sezzlepay\Model\Api\ProcessorInterface $sezzleApiProcessor
     * @param SezzleApiConfigInterface $sezzleApiConfig
     * @param \Magento\Sales\Api\Data\OrderInterface $orderInterface
     */
    public function __construct(
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Psr\Log\LoggerInterface $logger,
        \Sezzle\Sezzlepay\Model\Api\ProcessorInterface $sezzleApiProcessor,
        SezzleApiConfigInterface $sezzleApiConfig,
        \Magento\Sales\Api\Data\OrderInterface $orderInterface
    )
    {
        $this->orderFactory = $orderFactory;
        $this->sezzleApiConfig = $sezzleApiConfig;
        $this->sezzleApiProcessor = $sezzleApiProcessor;
        $this->logger = $logger;
        $this->orderInterface = $orderInterface;
    }


    /**
     * Send orders to Sezzle
     */
    public function sendOrdersToSezzle()
    {
        $today = date("Y-m-d H:i:s");
        $yesterday = date("Y-m-d H:i:s", strtotime("-1 days"));
        $yesterday = date('Y-m-d H:i:s', strtotime($yesterday));
        $today = date('Y-m-d H:i:s', strtotime($today));
        try {
            $ordersCollection = $this->orderFactory->create()->getCollection()
                ->addFieldToFilter(
                    'status',
                    [
                        'eq' => 'complete',
                        'eq' => 'processing'
                    ]
                )
                ->addAttributeToFilter(
                    'created_at',
                    [
                        'from' => $yesterday,
                        'to' => $today
                    ]
                )
                ->addAttributeToSelect('increment_id');
            $body = $this->_buildOrderPayLoad($ordersCollection);
            $url = $this->sezzleApiConfig->getSezzleBaseUrl() . '/v1/merchant_data' . '/magento/merchant_orders';
            $response = $this->sezzleApiProcessor->call(
                $url,
                $body,
                \Magento\Framework\HTTP\ZendClient::POST
            );
            $this->logger->debug(print_r($response));
        } catch (\Exception $e) {
            $this->logger->debug("Error while sending order to Sezzle" . $e->getMessage());
        }
    }

    /**
     * Build Payload
     *
     * @param null $ordersCollection
     * @return array
     */
    private function _buildOrderPayLoad($ordersCollection = null)
    {
        $body = [];
        if ($ordersCollection) {
            foreach ($ordersCollection as $orderObj) {
                $orderIncrementId = $orderObj->getIncrementId();
                $order = $this->orderInterface->loadByIncrementId($orderIncrementId);
                $payment = $order->getPayment();
                $billing = $order->getBillingAddress();

                $orderForSezzle = [
                    'order_number' => $orderIncrementId,
                    'payment_method' => $payment->getMethod(),
                    'amount' => $order->getGrandTotal() * 100,
                    'currency' => $order->getOrderCurrencyCode(),
                    'sezzle_reference' => $payment->getLastTransId(),
                    'customer_email' => $billing->getEmail(),
                    'customer_phone' => $billing->getTelephone(),
                    'billing_address1' => $billing->getStreetLine(1),
                    'billing_address2' => $billing->getStreetLine(2),
                    'billing_city' => $billing->getCity(),
                    'billing_state' => $billing->getRegionCode(),
                    'billing_postcode' => $billing->getPostcode(),
                    'billing_country' => $billing->getCountryId(),
                    'merchant_id' => $this->sezzleApiConfig->getMerchantId()
                ];
                array_push($body, $orderForSezzle);
            }
        }
        return $body;
    }
}