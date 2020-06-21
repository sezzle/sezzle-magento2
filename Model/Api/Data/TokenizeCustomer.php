<?php


namespace Sezzle\Payment\Model\Api\Data;


use Magento\Framework\Api\AbstractExtensibleObject;
use Sezzle\Payment\Api\Data\TokenizeCustomerInterface;

class TokenizeCustomer extends AbstractExtensibleObject implements TokenizeCustomerInterface
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

    /**
     * @inheritDoc
     */
    public function getLinks()
    {
        return $this->_get(self::LINKS);
    }

    /**
     * @inheritDoc
     */
    public function setLinks(array $links = null)
    {
        $this->setData(self::LINKS, $links);
    }
}
