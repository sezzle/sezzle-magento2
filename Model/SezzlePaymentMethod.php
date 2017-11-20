<?php

namespace Sezzle\Sezzlepay\Model;
class SezzlePaymentMethod extends \Magento\Payment\Model\Method\AbstractMethod
{
    protected $_code      = 'sezzlepay';
	protected $_isGateway = true;

	protected $_storeManager;
	protected $_logger;
	protected $_scopeConfig;
	protected $_urlBuilder;
	protected $_sezzleApi;

	const XML_PATH_PRIVATE_KEY = 'payment/sezzle/private_key';
	const XML_PATH_PUBLIC_KEY = 'payment/sezzle/public_key';
	const ADDITIONAL_INFORMATION_KEY_ORDERID = 'sezzle_order_id';

	public function __construct(
		\Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $mageLogger,
		\Magento\Store\Model\StoreManagerInterface $storeManager,
		\Psr\Log\LoggerInterface $logger,
		\Magento\Framework\UrlInterface $urlBuilder,
		\Sezzle\Sezzlepay\Model\Api $sezzleApi
	) {
		$this->_storeManager = $storeManager;
		$this->_logger = $logger;
		$this->_scopeConfig = $scopeConfig;
		$this->_urlBuilder = $urlBuilder;
		$this->_sezzleApi = $sezzleApi;
		parent::__construct(
			$context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $mageLogger
		);
	}

	protected function getStoreId()
    {
        return $this->_storeManager->getStore()->getId();
	}
	
	protected function getStoreCode()
    {
        return $this->_storeManager->getStore()->getCode();
	}

	protected function getStoreCurrencyCode()
    {
        return $this->_storeManager->getStore()->getCurrentCurrencyCode();
	}

	protected function getSezzleAPIURL() {
		return $this->_scopeConfig->getValue('payment/sezzlepay/base_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}

	protected function signRequest($data, $privateKey) {
		$ver = explode('.', phpversion());
		$major = (int) $ver[0];
		$minor = (int) $ver[1];
		if ($major >= 5 and $minor >= 4) {
			ksort($data, SORT_STRING | SORT_FLAG_CASE);
		} else {
			uksort($data, 'strcasecmp');
		}

		// Create the message
		$message = "";
		foreach ($data as $key => $value) {
			$message .= "$key$value";
		}

		$this->_logger->info("message : $message");
		$this->_logger->info("signkey : $privateKey");

		// Create sign
		$sign = hash_hmac("sha256", $message, $privateKey);
		$data['x_signature'] = $sign;
		$this->_logger->info("sign : $sign");
		return $data;
	}

	public function buildSezzlepayRequest($order)
	{

		$storeId = $this->getStoreId();
		$this->_logger->info("Store ID : $storeId");

		$storeCode = $this->getStoreCode();
		$this->_logger->info("Store Code : $storeCode");

		$orderID = $order->getIncrementId();
		$this->_logger->info("orderId : $orderID");

		$accountID = $this->_scopeConfig->getValue(self::XML_PATH_PUBLIC_KEY, 'default');
		$this->_logger->info("accountID : $accountID");

		$privateKey = $this->_scopeConfig->getValue(self::XML_PATH_PRIVATE_KEY, 'default');
		$this->_logger->info("privateKey : $privateKey");

		$amount = $order->getGrandTotal();
		$this->_logger->info("amount : $amount");

		$currency = $this->getStoreCurrencyCode();
		$this->_logger->info("currency : $currency");

		// billing address
		$billingAddress = $order->getBillingAddress();
		$billingAddressOne = $billingAddress->getStreetLine(1);
		$billingAddressTwo = $billingAddress->getStreetLine(2);
		$billingCity = $billingAddress->getCity();
		$billingPhone = $billingAddress->getTelephone();
		$billingZip = $billingAddress->getPostcode();
		$billingState = $billingAddress->getRegionCode();
		$billingCountry = $billingAddress->getCountryId();
		$this->_logger->info("Billing address received");

		// User details
		$email = $order->getCustomerEmail();
		$firstName = $order->getCustomerFirstname();
		$lastName = $order->getCustomerLastname();
		$phone = $billingAddress->getTelephone();
		$this->_logger->info("User details received");

		// Shipping address
		$shippingAddress = $order->getShippingAddress();
		$shippingAddressOne = $shippingAddress->getStreetLine(1);
		$shippingAddressTwo = $shippingAddress->getStreetLine(2);
		$shippingCity = $shippingAddress->getCity();
		$shippingPhone = $shippingAddress->getTelephone();
		$shippingZip = $shippingAddress->getPostcode();
		$shippingState = $shippingAddress->getRegionCode();
		$shippingCountry = $shippingAddress->getCountryId();
		$shippingFirstname = $shippingAddress->getFirstname();
		$shippingLastname = $shippingAddress->getLastname();
		$this->_logger->info("Shipping address received");

		// Reference
		$reference = $orderID;
		$countryCode = $this->_scopeConfig->getValue('general/store_information/country_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		$this->_logger->info("Country ID: $countryCode");
		$shopName = $this->_storeManager->getStore()->getFrontendName();
		$testMode = false;
		$tranID = uniqid() . "-" . $orderID;
		$this->_logger->info("Transaction ID received");

		// URLS
		$completeUrl = $this->_urlBuilder->getUrl("sezzlepay/standard/complete/id/$tranID", ['_secure' => true]);
		$cancelUrl = $this->_urlBuilder->getUrl("sezzlepay/standard/cancel/id/$tranID", ['_secure' => true]);
		$this->_logger->info("Redirect URLs received");

		$requestData = array(
			"x_account_id" => $accountID,
			"x_amount" => $amount,
			"x_currency" => $currency,
			"x_customer_billing_address1" => $billingAddressOne,
			"x_customer_billing_address2" => $billingAddressTwo,
			"x_customer_billing_city" => $billingCity,
			"x_customer_billing_country" => $billingCountry,
			"x_customer_billing_phone" => $billingPhone,
			"x_customer_billing_zip" => $billingZip,
			"x_customer_billing_state" => $billingState,
			"x_customer_email" => $email,
			"x_customer_first_name" => $firstName,
			"x_customer_last_name" => $lastName,
			"x_customer_phone" => $phone,
			"x_customer_shipping_address1" => $shippingAddressOne,
			"x_customer_shipping_address2" => $shippingAddressTwo,
			"x_customer_shipping_city" => $shippingCity,
			"x_customer_shipping_country" => $shippingCountry,
			"x_customer_shipping_first_name" => $shippingFirstname,
			"x_customer_shipping_last_name" => $shippingLastname,
			"x_customer_shipping_phone" => $shippingPhone,
			"x_customer_shipping_zip" => $shippingZip,
			"x_customer_shipping_state" => $shippingState,
			"x_reference" => $orderID,
			"x_shop_country" => $countryCode,
			"x_shop_name" => $shopName,
			"x_test" => $testMode ? 1 : 0,
			'x_url_complete' => $completeUrl,
			'x_url_cancel' => $cancelUrl,
		);

		// Sign the data
		$requestData = $this->signRequest($requestData, $privateKey);
		return array(
			"data" => $requestData,
			"redirectURL" => $this->getSezzleAPIURL(),
		);
	}

	public function getSezzleRedirectUrl($quote, $reference) {
		$orderId = $quote->getReservedOrderId();
		$billingAddress  = $quote->getBillingAddress();
		$shippingAddress = $quote->getShippingAddress();
		$completeUrl = $this->_urlBuilder->getUrl("sezzlepay/standard/complete/id/$orderId/magento_sezzle_id/$reference", ['_secure' => true]);
		$cancelUrl = $this->_urlBuilder->getUrl("sezzlepay/standard/cancel", ['_secure' => true]);

		$requestBody = array();
		$requestBody["amount_in_cents"] = $quote->getGrandTotal() * 100;
        $requestBody["currency_code"] = $this->getStoreCurrencyCode();
        $requestBody["order_description"] = $reference;
        $requestBody["order_reference_id"] = $reference;
        $requestBody["display_order_reference_id"] = $orderId;
        $requestBody["checkout_cancel_url"] = $cancelUrl;
        $requestBody["checkout_complete_url"] = $completeUrl;
        $requestBody["customer_details"] = array(
            "first_name" => $quote->getCustomerFirstname() ? $quote->getCustomerFirstname() : $billingAddress->getFirstname(),
            "last_name" => $quote->getCustomerLastname() ? $quote->getCustomerLastname() : $billingAddress->getLastname(),
            "email" => $quote->getCustomerEmail(),
            "phone" => $billingAddress->getTelephone()
        );
        $requestBody["billing_address"] = array(
            "street" => $billingAddress->getStreetLine(1),
            "street2" => $billingAddress->getStreetLine(2),
            "city" => $billingAddress->getCity(),
            "state" => $billingAddress->getRegionCode(),
            "postal_code" => $billingAddress->getPostcode(),
            "country_code" => $billingAddress->getCountryId(),
            "phone" => $billingAddress->getTelephone()
        );
        $requestBody["shipping_address"] = array(
            "street" => $shippingAddress->getStreetLine(1),
            "street2" => $shippingAddress->getStreetLine(2),
            "city" => $shippingAddress->getCity(),
            "state" => $shippingAddress->getRegionCode(),
            "postal_code" => $shippingAddress->getPostcode(),
            "country_code" => $shippingAddress->getCountryId(),
            "phone" => $shippingAddress->getTelephone()
        );
        $requestBody["items"] = array();
        foreach ($quote->getAllVisibleItems() as $item) {
            $productName = $item->getName();
            $productPrice = $item->getPriceInclTax() * 100;
            $productSKU = $item->getSku();
            $productQuantity = $item->getQtyOrdered();
            $itemData = array(
                "name" => $productName,
                "sku" => $productSKU,
                "quantity" => $productQuantity,
                "price" => array(
                    "amount_in_cents" => $productPrice,
                    "currency" => $this->getStoreCurrencyCode()
                )
            );
            array_push($requestBody["items"], $itemData);
        }

		$requestBody["merchant_completes"] = true;
		
		try {
            $response = $this->_sezzleApi->call(
                $this->getSezzleAPIURL() . '/v1/checkouts',
                $requestBody,
                \Magento\Framework\HTTP\ZendClient::POST
            );
        } catch (\Exception $e) {
            $this->helper->debug($e->getMessage());
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));       
        }
        return $response;
	}

	public function capturePayment($reference) {
		try {
            $response = $this->_sezzleApi->call(
                $this->getSezzleAPIURL() . '/v1/checkouts' . '/' . $reference . '/complete',
                null,
                \Magento\Framework\HTTP\ZendClient::POST
			);
			$responseBody = $response->getBody();
            $debugData['response'] = $responseBody;
        } catch (\Exception $e) {
            $this->helper->debug($e->getMessage());
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));       
        }
        return $response;
	}
}