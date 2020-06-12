<?php


namespace Sezzle\Payment\Model\Api\Data;


use Magento\Framework\Api\AbstractExtensibleObject;
use Sezzle\Payment\Api\Data\LinkInterface;

class Link extends AbstractExtensibleObject implements LinkInterface
{

    /**
     * @inheritDoc
     */
    public function getHref()
    {
        return $this->_get(self::HREF);
    }

    /**
     * @inheritDoc
     */
    public function setHref($href)
    {
        $this->setData(self::HREF, $href);
    }

    /**
     * @inheritDoc
     */
    public function getRel()
    {
        return $this->_get(self::REL);
    }

    /**
     * @inheritDoc
     */
    public function setRel($rel)
    {
        $this->setData(self::REL, $rel);
    }

    /**
     * @inheritDoc
     */
    public function getMethod()
    {
        return $this->_get(self::METHOD);
    }

    /**
     * @inheritDoc
     */
    public function setMethod($method)
    {
        $this->setData(self::METHOD, $method);
    }
}
