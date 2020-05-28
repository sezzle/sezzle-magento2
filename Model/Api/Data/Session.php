<?php


namespace Sezzle\Sezzlepay\Model\Api\Data;

use Magento\Framework\Api\AbstractExtensibleObject;
use Sezzle\Sezzlepay\Api\Data\SessionInterface;
use Sezzle\Sezzlepay\Api\Data\SessionOrderInterface;
use Sezzle\Sezzlepay\Api\Data\SessionTokenizeInterface;

class Session extends AbstractExtensibleObject implements SessionInterface
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
    public function getOrder()
    {
        return $this->_get(self::ORDER);
    }

    /**
     * @inheritDoc
     */
    public function setOrder(SessionOrderInterface $sessionOrder = null)
    {
        $this->setData(self::ORDER, $sessionOrder);
    }

    /**
     * @inheritDoc
     */
    public function getTokenize()
    {
        return $this->_get(self::TOKENIZE);
    }

    /**
     * @inheritDoc
     */
    public function setTokenize(SessionTokenizeInterface $sessionTokenize = null)
    {
        $this->setData(self::TOKENIZE, $sessionTokenize);
    }
}
