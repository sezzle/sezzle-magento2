<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model\Gateway;

use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Sezzle\Sezzlepay\Helper\Data as SezzleHelper;
use Sezzle\Sezzlepay\Model\Api\ConfigInterface;
use Sezzle\Sezzlepay\Model\Config\Container\ProductWidgetConfigInterface;
use Sezzle\Sezzlepay\Model\Config\Container\SezzleApiConfigInterface;
use Sezzle\Sezzlepay\Model\SezzlePay;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Config as OrderConfig;

/**
 * Class ProcessPayment
 * @package Sezzle\Sezzlepay\Model\Gateway
 */
class ProcessPayment
{
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
     * @var ProductWidgetConfigInterface
     */
    private $productWidgetConfig;
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var SezzleHelper
     */
    private $sezzleHelper;

    /**
     * @var CollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var SezzlePay
     */
    private $sezzlePay;

    /**
     * @var OrderConfig
     */
    private $orderConfig;

    /**
     * Heartbeat constructor.
     * @param \Psr\Log\LoggerInterface $logger
     * @param SezzleHelper $sezzleHelper
     * @param ConfigInterface $config
     * @param \Sezzle\Sezzlepay\Model\Api\ProcessorInterface $sezzleApiProcessor
     * @param SezzleApiConfigInterface $sezzleApiConfig
     * @param ProductWidgetConfigInterface $productWidgetConfig
     * @param CollectionFactory $orderCollectionFactory
     * @param DateTime $dateTime
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        SezzleHelper $sezzleHelper,
        ConfigInterface $config,
        \Sezzle\Sezzlepay\Model\Api\ProcessorInterface $sezzleApiProcessor,
        SezzleApiConfigInterface $sezzleApiConfig,
        ProductWidgetConfigInterface $productWidgetConfig,
        CollectionFactory $orderCollectionFactory,
        DateTime $dateTime,
        SezzlePay $sezzlePay,
        OrderConfig $orderConfig
    ) {
        $this->sezzleApiConfig = $sezzleApiConfig;
        $this->sezzleHelper = $sezzleHelper;
        $this->config = $config;
        $this->productWidgetConfig = $productWidgetConfig;
        $this->sezzleApiProcessor = $sezzleApiProcessor;
        $this->logger = $logger;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->dateTime = $dateTime;
        $this->sezzlePay = $sezzlePay;
        $this->orderConfig = $orderConfig;
    }

    /**
     * Sending hearbeat to Sezzle
     */
    public function send()
    {
        $nonCapturedOrders = $this->orderCollectionFactory->create()
            ->addAttributeToFilter("is_captured", SezzlePay::STATE_NOT_CAPTURED);
        $currentTimestamp = $this->dateTime->timestamp("now");
        foreach ($nonCapturedOrders as $order) {
            $captureExpiration = $order->getPayment()
                ->getAdditionalInformation(SezzlePay::SEZZLE_CAPTURE_EXPIRY);
            $referenceOrderID = $order->getPayment()->getAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_ORDERID);
            $captureExpirationTimestamp = $this->dateTime->timestamp($captureExpiration);
            $paymentType = $order->getPayment()->getAdditionalInformation("payment_type");
            if (($captureExpirationTimestamp >= $currentTimestamp) && $paymentType == Sezzle_Sezzlepay_Model_Sezzlepay::AUTH_CAPTURE) {
                $response = $this->sezzlePay->sezzleCapture($referenceOrderID);
                $response = $this->jsonHelper->jsonDecode($response, true);
                if (isset($response["captured_at"]) && $response["captured_at"]) {
                    $order->setState(Order::STATE_PROCESSING, true)
                        ->setStatus($this->orderConfig->getStateDefaultStatus(Order::STATE_PROCESSING))
                        ->save();
                }
            }
        }
    }
}
