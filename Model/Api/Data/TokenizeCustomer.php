<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model\Api\Data;


use Magento\Framework\Api\AbstractExtensibleObject;
use Sezzle\Sezzlepay\Api\Data\TokenizeCustomerInterface;

class TokenizeCustomer extends AbstractExtensibleObject implements TokenizeCustomerInterface
{

    /**
     * @inheritDoc
     */
    public function getUuid(): string
    {
        return $this->_get(self::UUID);
    }

    /**
     * @inheritDoc
     */
    public function setUuid(string $uuid): self
    {
        $this->setData(self::UUID, $uuid);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getExpiration(): string
    {
        return $this->_get(self::EXPIRATION);
    }

    /**
     * @inheritDoc
     */
    public function setExpiration(string $expiration): self
    {
        $this->setData(self::EXPIRATION, $expiration);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getLinks(): ?array
    {
        return $this->_get(self::LINKS);
    }

    /**
     * @inheritDoc
     */
    public function setLinks(?array $links = null): self
    {
        $this->setData(self::LINKS, $links);
        return $this;
    }
}
