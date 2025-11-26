<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

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
    public function getOrder(): ?SessionOrderInterface
    {
        return $this->_get(self::ORDER);
    }

    /**
     * @inheritDoc
     */
    public function setOrder(?SessionOrderInterface $sessionOrder = null): self
    {
        $this->setData(self::ORDER, $sessionOrder);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getTokenize(): ?SessionTokenizeInterface
    {
        return $this->_get(self::TOKENIZE);
    }

    /**
     * @inheritDoc
     */
    public function setTokenize(?SessionTokenizeInterface $sessionTokenize = null): self
    {
        $this->setData(self::TOKENIZE, $sessionTokenize);
        return $this;
    }
}
