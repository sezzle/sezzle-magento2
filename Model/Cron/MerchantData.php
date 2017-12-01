<?php

namespace Sezzle\Sezzlepay\Model\Cron;

class MerchantData
{
    protected $orderFactory;
    protected $jsonHelper;
    protected $date;
    protected $timezone;
    protected $logger;
	protected $scopeConfig;
	protected $urlBuilder;
    protected $sezzleApi;
    protected $orderInterface;

    public function __construct(
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Sezzle\Sezzlepay\Model\Api $sezzleApi,
        \Magento\Sales\Api\Data\OrderInterface $orderInterface
    ) {
        $this->orderFactory = $orderFactory;

        $this->jsonHelper = $jsonHelper;
        $this->date = $date;
        $this->timezone = $timezone;

        $this->logger = $logger;
		$this->scopeConfig = $scopeConfig;
		$this->urlBuilder = $urlBuilder;
        $this->sezzleApi = $sezzleApi;
        $this->orderInterface = $orderInterface;
    }

    public function execute()
    {
        $this->sendOrdersToSezzle();
    }

    private function sendOrdersToSezzle() {
        $today = date("Y-m-d H:i:s");
        $yesterday = date("Y-m-d H:i:s", strtotime("-1 days"));

        $yesterday = date('Y-m-d H:i:s', strtotime($yesterday));
        $today = date('Y-m-d H:i:s', strtotime($today));
        $ordersCollection = $this->orderFactory->create()->getCollection()
            ->addFieldToFilter('status',
                array(
                    'eq' => 'complete',
                    'eq' => 'processing'
                )
            )
            // Get last day to today
            ->addAttributeToFilter('created_at',
                array(
                    'from' => $yesterday,
                    'to' => $today
                )
            )
            ->addAttributeToSelect('increment_id');
        $body = array();
        foreach ($ordersCollection as $orderObj) {
            $orderIncrementId = $orderObj->getIncrementId();
            $order = $this->orderInterface->loadByIncrementId($orderIncrementId);
            $payment = $order->getPayment();
            $billing = $order->getBillingAddress();

            $orderForSezzle = array(
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
                'billing_country' => $billing->getCountryId()
            );
            array_push($body, $orderForSezzle);
        }
        $this->logger->debug(json_encode($body));
    }
}