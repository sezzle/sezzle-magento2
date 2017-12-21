<?php
namespace Sezzle\Sezzlepay\Controller\Standard;

class Redirect extends \Sezzle\Sezzlepay\Controller\Sezzlepay
{
    public function execute()
    {
        $quote = $this->_checkoutSession->getQuote();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerSession = $objectManager->get('Magento\Customer\Model\Session');
        $customerRepository = $objectManager->get('Magento\Customer\Api\CustomerRepositoryInterface');
        if ($customerSession->isLoggedIn()) {
            $customerId = $customerSession->getCustomer()->getId();
            $customer = $customerRepository->getById($customerId);
            $quote->setCustomer($customer);
            $billingAddress  = $quote->getBillingAddress();
            $shippingAddress = $quote->getShippingAddress();
            if (empty($shippingAddress) || empty($shippingAddress->getStreetLine(1)) && empty($billingAddress) || empty($billingAddress->getStreetLine(1))) {
                $json = json_encode(["message" => "Please select an Address"]);
                $jsonResult = $this->_resultJsonFactory->create();
                $jsonResult->setData($json);
                return $jsonResult;
            } elseif (empty($shippingAddress) || empty($shippingAddress->getStreetLine(1)) || empty($shippingAddress->getFirstname())) {
                $shippingAddress = $quote->getBillingAddress();
                $quote->setShippingAddress($object->getBillingAddress());
            } elseif (empty($billingAddress) || empty($billingAddress->getStreetLine(1)) || empty($billingAddress->getFirstname())) {
                $billingAddress = $quote->getShippingAddress();
                $quote->setBillingAddress($object->getShippingAddress());
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

        $orderUrl = $this->_getSezzleRedirectUrl($quote);
        $json = json_encode(["redirectURL" => $orderUrl]);
        $jsonResult = $this->_resultJsonFactory->create();
        $jsonResult->setData($json);
        return $jsonResult;
    }

    private function createUniqueReferenceId($referenceId)
    {
        return uniqid() . "-" . $referenceId;
    }

    private function _getSezzleRedirectUrl($quote)
    {
        $reference = $this->createUniqueReferenceId($quote->getReservedOrderId());
        $payment = $quote->getPayment();
        $payment->setAdditionalInformation(\Sezzle\Sezzlepay\Model\SezzlePaymentMethod::ADDITIONAL_INFORMATION_KEY_ORDERID, $reference);
        $payment->save();
        $response = $this->getSezzlepayModel()->getSezzleRedirectUrl($quote, $reference);
        $result = $this->_jsonHelper->jsonDecode($response->getBody(), true);
        $orderUrl = array_key_exists('checkout_url', $result) ? $result['checkout_url'] : false;
        if (!$orderUrl) {
            $this->_logger->info("No Token response from API");
            throw new \Magento\Framework\Exception\LocalizedException(__('There is an issue processing your order.'));
        }
        return $orderUrl;
    }
}
