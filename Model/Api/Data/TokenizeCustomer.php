<?php


namespace Sezzle\Sezzlepay\Model\Api\Data;


use Magento\Framework\Api\AbstractExtensibleObject;
use Sezzle\Sezzlepay\Api\Data\TokenizeCustomerInterface;

class TokenizeCustomer extends AbstractExtensibleObject implements TokenizeCustomerInterface
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
    public function getExpiration()
    {
        return $this->_get(self::EXPIRATION);
    }

    /**
     * @inheritDoc
     */
    public function setExpiration($expiration)
    {
        $this->setData(self::EXPIRATION, $expiration);
    }
}
