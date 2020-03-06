<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model\Gateway;

use Sezzle\Sezzlepay\Helper\Data as SezzleHelper;
use Sezzle\Sezzlepay\Model\Api\ConfigInterface;
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
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var SezzleHelper
     */
    private $sezzleHelper;

    /**
     * Transaction constructor.
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param SezzleHelper $sezzleHelper
     * @param ConfigInterface $config
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Sezzle\Sezzlepay\Model\Api\ProcessorInterface $sezzleApiProcessor
     * @param SezzleApiConfigInterface $sezzleApiConfig
     * @param \Magento\Sales\Api\Data\OrderInterface $orderInterface
     */
    public function __construct(
        \Magento\Sales\Model\OrderFactory $orderFactory,
        SezzleHelper $sezzleHelper,
        ConfigInterface $config,
        \Psr\Log\LoggerInterface $logger,
        \Sezzle\Sezzlepay\Model\Api\ProcessorInterface $sezzleApiProcessor,
        SezzleApiConfigInterface $sezzleApiConfig,
        \Magento\Sales\Api\Data\OrderInterface $orderInterface
    ) {
        $this->orderFactory = $orderFactory;
        $this->sezzleHelper = $sezzleHelper;
        $this->config = $config;
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
        $this->sezzleHelper->logSezzleActions("****Order sync process start****");
        $today = date("Y-m-d H:i:s");
        $this->sezzleHelper->logSezzleActions("Current date : $today");
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
            $authToken = $this->config->getAuthToken();
            $this->sezzleApiProcessor->call(
                $url,
                $authToken,
                $body,
                \Magento\Framework\HTTP\ZendClient::POST
            );
            $this->sezzleHelper->logSezzleActions("****Order sync process end****");
        } catch (\Exception $e) {
            $this->sezzleHelper->logSezzleActions("Error while sending order to Sezzle" . $e->getMessage());
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
                    'amount' => round((float)$order->getGrandTotal(), \Sezzle\Sezzlepay\Model\Api\PayloadBuilder::PRECISION) * 100,
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
