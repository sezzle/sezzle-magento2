<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model\Api\Data;


use Magento\Framework\Api\AbstractExtensibleObject;
use Sezzle\Sezzlepay\Api\Data\LinkInterface;

class Link extends AbstractExtensibleObject implements LinkInterface
{

    /**
     * @inheritDoc
     */
    public function getHref(): string
    {
        return $this->_get(self::HREF);
    }

    /**
     * @inheritDoc
     */
    public function setHref(string $href): self
    {
        $this->setData(self::HREF, $href);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getRel(): string
    {
        return $this->_get(self::REL);
    }

    /**
     * @inheritDoc
     */
    public function setRel(string $rel): self
    {
        $this->setData(self::REL, $rel);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getMethod(): string
    {
        return $this->_get(self::METHOD);
    }

    /**
     * @inheritDoc
     */
    public function setMethod(string $method): self
    {
        $this->setData(self::METHOD, $method);
        return $this;
    }
}
