<?php


namespace Sezzle\Sezzlepay\Model\Api\Data;


use Magento\Framework\Api\AbstractExtensibleObject;
use Sezzle\Sezzlepay\Api\Data\AmountInterface;
use Sezzle\Sezzlepay\Api\Data\PaymentActionInterface;

class PaymentAction extends AbstractExtensibleObject implements PaymentActionInterface
{

    /**
     * @inheritDoc
     */
    public function getUUID()
    {
        $this->_get(self::UUID);
    }

    /**
     * @inheritDoc
     */
    public function setUUID($uuid)
    {
        $this->setData(self::UUID, $uuid);
    }

    /**
     * @inheritDoc
     */
    public function getAmount()
    {
        return $this->_get(self::AMOUNT);
    }

    /**
     * @inheritDoc
     */
    public function setAmount(AmountInterface $amount)
    {
        $this->setData(self::AMOUNT, $amount);
    }
}
