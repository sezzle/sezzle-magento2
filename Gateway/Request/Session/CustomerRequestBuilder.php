<?php

namespace Sezzle\Sezzlepay\Gateway\Request\Session;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Sezzle\Sezzlepay\Gateway\Config\Config;


/**
 * CustomerRequestBuilder
 */
class CustomerRequestBuilder implements BuilderInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function build(array $buildSubject): array
    {
        /** @var Quote $quote */
        $quote = $buildSubject['quote'];

        try {
            $tokenize = !$this->config->isInContextModeActive() && $this->config->isTokenizationEnabled();
        } catch (InputException|NoSuchEntityException $e) {
            $tokenize = false;
        }

        return [
            'customer' => [
                'tokenize' => $tokenize,
                'email' => $quote->getCustomerEmail(),
                'first_name' => $quote->getCustomerFirstname() ?: $quote->getBillingAddress()->getFirstname(),
                'last_name' => $quote->getCustomerLastname() ?: $quote->getBillingAddress()->getLastname(),
                'phone' => $quote->getBillingAddress()->getTelephone(),
                'dob' => $quote->getCustomer()->getDob(),
                'billing_address' => $this->buildAddressPayload($quote->getBillingAddress()),
                'shipping_address' => $this->buildAddressPayload($quote->getShippingAddress()),
            ]
        ];
    }

    /**
     * Build address payload
     * @param Address $address
     * @return array
     */
    private function buildAddressPayload(Address $address): array
    {
        return [
            'name' => $address->getName(),
            'street' => $address->getStreetLine(1),
            'street2' => $address->getStreetLine(2),
            'city' => $address->getCity(),
            'state' => $address->getRegionCode(),
            'postal_code' => $address->getPostcode(),
            'country_code' => $address->getCountryId(),
            'phone' => $address->getTelephone()
        ];
    }
}
