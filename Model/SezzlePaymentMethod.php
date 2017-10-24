<?php

namespace Sezzle\Pay\Model;
class SezzlePaymentMethod extends \Magento\Payment\Model\Method\AbstractMethod
{
	protected $_isInitializeNeeded      = false;
    protected $redirect_uri;
    protected $_code = 'sezzle';
 	protected $_canOrder = true;
	protected $_isGateway = true; 
	
    public function getOrderPlaceRedirectUrl() {
	   return \Magento\Framework\App\ObjectManager::getInstance()
							->get('Magento\Framework\UrlInterface')->getUrl("sezzle/redirect");
   } 
}