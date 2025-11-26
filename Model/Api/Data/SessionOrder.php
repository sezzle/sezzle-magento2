<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model\Api\Data;


use Magento\Framework\Api\AbstractExtensibleObject;
use Sezzle\Sezzlepay\Api\Data\SessionOrderInterface;

class SessionOrder extends AbstractExtensibleObject implements SessionOrderInterface
{

    /**
     * @inheritDoc
     */
    public function getUuid(): ?string
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
    public function getCheckoutUrl(): ?string
    {
        return $this->_get(self::CHECKOUT_URL);
    }

    /**
     * @inheritDoc
     */
    public function setCheckoutUrl(string $checkoutURL): self
    {
        $this->setData(self::CHECKOUT_URL, $checkoutURL);
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
