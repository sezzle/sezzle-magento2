<?php

namespace Sezzle\Sezzlepay\Gateway\Request\Session;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\Resolver;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Quote\Model\Quote;
use Sezzle\Sezzlepay\Gateway\Config\Config;
use Sezzle\Sezzlepay\Helper\Util;


/**
 * OrderRequestBuilder
 */
class OrderRequestBuilder implements BuilderInterface
{
    /**
     * @var Resolver
     */
    private $localeResolver;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param Config $config
     * @param Resolver $localeResolver
     */
    public function __construct(
        Config   $config,
        Resolver $localeResolver
    )
    {
        $this->config = $config;
        $this->localeResolver = $localeResolver;
    }

    /**
     * @inheritDoc
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function build(array $buildSubject): array
    {
        /** @var Quote $quote */
        $quote = $buildSubject['quote'];
        $referenceID = $buildSubject['reference_id'];

        $result = [];


        $result['order'] = [
            'intent' => 'AUTH',
            'reference_id' => $referenceID,
            'description' => $quote->getStore()->getName(),
            'requires_shipping_info' => false,
            'items' => $this->buildItemsPayload($quote),
            'discounts' => [
                $this->getPriceObject(
                    $quote->getShippingAddress()->getBaseDiscountAmount(),
                    $quote->getBaseCurrencyCode()
                )
            ],
            'shipping_amount' => $this->getPriceObject(
                $quote->getShippingAddress()->getBaseShippingAmount(),
                $quote->getBaseCurrencyCode()
            ),
            'tax_amount' => $this->getPriceObject(
                $quote->getShippingAddress()->getBaseTaxAmount(),
                $quote->getBaseCurrencyCode()
            ),
            'order_amount' => $this->getPriceObject($quote->getBaseGrandTotal(), $quote->getBaseCurrencyCode()),
            'locale' => $this->localeResolver->getLocale(),
        ];

        if ($this->config->isInContextModeActive()) {
            $result['checkout_mode'] = $this->config->getInContextMode();
        }

        return $result;
    }

    /**
     * Build cart items payload
     * @param Quote $quote
     * @return array
     */
    private function buildItemsPayload(Quote $quote): array
    {
        $itemPayload = [];
        foreach ($quote->getAllVisibleItems() as $item) {
            $productName = $item->getName();
            $productSku = $item->getSku();
            $productQuantity = $item->getQty();
            $itemData = [
                'name' => $productName,
                'sku' => $productSku,
                'quantity' => $productQuantity,
                'price' => $this->getPriceObject(
                    $item->getPriceInclTax(),
                    $quote->getBaseCurrencyCode()
                )
            ];
            $itemPayload[] = $itemData;
        }
        return $itemPayload;
    }

    /**
     * Get Price Object
     *
     * @param float $amount
     * @param string $currency
     * @return array
     */
    private function getPriceObject(float $amount, string $currency): array
    {
        return [
            'amount_in_cents' => Util::formatToCents($amount),
            'currency' => $currency
        ];
    }
}
