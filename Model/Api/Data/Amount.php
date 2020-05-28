<?php


namespace Sezzle\Sezzlepay\Model\Api\Data;


use Magento\Framework\Api\AbstractExtensibleObject;
use Sezzle\Sezzlepay\Api\Data\AmountInterface;

class Amount extends AbstractExtensibleObject implements AmountInterface
{

    /**
     * @inheritDoc
     */
    public function getAmountInCents()
    {
        return $this->_get(self::AMOUNT_IN_CENTS);
    }

    /**
     * @inheritDoc
     */
    public function setAmountInCents($amountInCents)
    {
        $this->setData(self::AMOUNT_IN_CENTS, $amountInCents);
    }

    /**
     * @inheritDoc
     */
    public function getCurrency()
    {
        return $this->_get(self::CURRENCY);
    }

    /**
     * @inheritDoc
     */
    public function setCurrency($currency)
    {
        $this->setData(self::CURRENCY, $currency);
    }
}
