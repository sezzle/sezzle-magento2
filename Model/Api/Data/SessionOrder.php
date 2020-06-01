<?php


namespace Sezzle\Payment\Model\Api\Data;


use Magento\Framework\Api\AbstractExtensibleObject;
use Sezzle\Payment\Api\Data\SessionOrderInterface;

class SessionOrder extends AbstractExtensibleObject implements SessionOrderInterface
{

    /**
     * @inheritDoc
     */
    public function getUuid()
    {
        return $this->_get(self::UUID);
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
    public function getCheckoutUrl()
    {
        return $this->_get(self::CHECKOUT_URL);
    }

    /**
     * @inheritDoc
     */
    public function setCheckoutUrl($checkoutURL)
    {
        $this->setData(self::CHECKOUT_URL, $checkoutURL);
    }
}
