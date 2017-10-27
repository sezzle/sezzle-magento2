<?php

namespace Sezzle\Sezzlepay\Model;
class SezzlePaymentMethod extends \Magento\Payment\Model\Method\AbstractMethod
{
    protected $_code      = 'sezzlepay';
	protected $_isGateway = true;

	protected $_storeManager;
	protected $_logger;

	public function __construct(
		\Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $mageLogger,
		\Magento\Store\Model\StoreManagerInterface $storeManager,
		\Psr\Log\LoggerInterface $logger
	) {
		$this->_storeManager = $storeManager;
		$this->_logger = $logger;
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

	protected function getSezzleAPIURL() {
		// TODO: Do it based on api mode
		return "http://127.0.0.1:9001/v1";
	}


	public function buildSezzlepayRequest($order)
	{
		$storeId = $this->getStoreId();
		$this->_logger->info("Store ID : $storeId");

		$storeCode = $this->getStoreCode();
		$this->_logger->info("Store Code : $storeCode");

		$orderID = $order->getIncrementId();
		$this->_logger->info("orderId : $orderID");

		$accountID = $this->getConfigData("public_key");
		$this->_logger->info("accountID : $accountID");

		$url = $this->getSezzleAPIURL();
		$this->_logger->info("url : $url");
		return $url;
	}
}