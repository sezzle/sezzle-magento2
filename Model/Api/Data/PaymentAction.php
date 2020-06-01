<?php


namespace Sezzle\Payment\Model\Api\Data;


use Magento\Framework\Api\AbstractExtensibleObject;
use Sezzle\Payment\Api\Data\AmountInterface;
use Sezzle\Payment\Api\Data\PaymentActionInterface;

class PaymentAction extends AbstractExtensibleObject implements PaymentActionInterface
{

    /**
     * @inheritDoc
     */
    public function getUuid()
    {
        $this->_get(self::UUID);
    }

    /**
     * @inheritDoc
     */
    public function setUuid($uuid)
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
