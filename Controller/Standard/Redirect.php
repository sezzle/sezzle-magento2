<?php

namespace Sezzle\Sezzlepay\Controller\Standard;

use Sezzle\Sezzlepay\Controller\AbstractController\SezzlePay;

/**
 * Class Redirect
 * @package Sezzle\Sezzlepay\Controller\Standard
 */
class Redirect extends SezzlePay
{
    /**
     * Redirection
     *
     * @return mixed
     */
    public function execute()
    {
        $quote = $this->_checkoutSession->getQuote();
        if ($this->_customerSession->isLoggedIn()) {
            $customerId = $this->_customerSession->getCustomer()->getId();
            $customer = $this->_customerRepository->getById($customerId);
            $quote->setCustomer($customer);
            $billingAddress = $quote->getBillingAddress();
            $shippingAddress = $quote->getShippingAddress();
            if ((empty($shippingAddress) || empty($shippingAddress->getStreetLine(1))) && (empty($billingAddress) || empty($billingAddress->getStreetLine(1)))) {
                $json = $this->_jsonHelper->jsonEncode(["message" => "Please select an address"]);
                $jsonResult = $this->_resultJsonFactory->create();
                $jsonResult->setData($json);
                return $jsonResult;
            } elseif (empty($billingAddress) || empty($billingAddress->getStreetLine(1)) || empty($billingAddress->getFirstname())) {
                $quote->setBillingAddress($shippingAddress);
            }
        } else {
            $post = $this->getRequest()->getPostValue();
            if (!empty($post['email'])) {
                $quote->setCustomerEmail($post['email'])
                    ->setCustomerIsGuest(true)
                    ->setCustomerGroupId(\Magento\Customer\Api\Data\GroupInterface::NOT_LOGGED_IN_ID);
            }
        }
        $payment = $quote->getPayment();
        $payment->setMethod('sezzlepay');
        $quote->reserveOrderId();
        $quote->setPayment($payment);
        $quote->save();
        $this->_checkoutSession->replaceQuote($quote);
        $checkoutUrl = $this->_sezzlepayModel->getSezzleCheckoutUrl($quote);
        $json = $this->_jsonHelper->jsonEncode(["redirectURL" => $checkoutUrl]);
        $jsonResult = $this->_resultJsonFactory->create();
        $jsonResult->setData($json);
        return $jsonResult;
    }
}
