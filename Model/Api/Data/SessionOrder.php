<?php


namespace Sezzle\Sezzlepay\Model\Api\Data;


use Magento\Framework\Api\AbstractExtensibleObject;
use Sezzle\Sezzlepay\Api\Data\SessionOrderInterface;

class SessionOrder extends AbstractExtensibleObject implements SessionOrderInterface
{

    /**
     * @inheritDoc
     */
    public function getUUID()
    {
        return $this->_get(self::UUID);
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
    public function getCheckoutURL()
    {
        return $this->_get(self::CHECKOUT_URL);
    }

    /**
     * @inheritDoc
     */
    public function setCheckoutURL($checkoutURL)
    {
        $this->setData(self::CHECKOUT_URL, $checkoutURL);
    }
}
