<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model\Api\Data;


use Magento\Framework\Api\AbstractExtensibleObject;
use Sezzle\Sezzlepay\Api\Data\SessionTokenizeInterface;
use Sezzle\Sezzlepay\Api\Data\TokenizeCustomerInterface;

class SessionTokenize extends AbstractExtensibleObject implements SessionTokenizeInterface
{

    /**
     * @inheritDoc
     */
    public function getStatus(): ?string
    {
        return $this->_get(self::STATUS);
    }

    /**
     * @inheritDoc
     */
    public function setStatus(string $status): self
    {
        $this->setData(self::STATUS, $status);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getToken(): ?string
    {
        return $this->_get(self::TOKEN);
    }

    /**
     * @inheritDoc
     */
    public function setToken(string $token): self
    {
        $this->setData(self::TOKEN, $token);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getApprovalUrl(): ?string
    {
        return $this->_get(self::APPROVAL_URL);
    }

    /**
     * @inheritDoc
     */
    public function setApprovalUrl(string $approvalURL): self
    {
        $this->setData(self::APPROVAL_URL, $approvalURL);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getExpiration(): ?string
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
    public function getCustomer(): ?TokenizeCustomerInterface
    {
        return $this->_get(self::CUSTOMER);
    }

    /**
     * @inheritDoc
     */
    public function setCustomer(?TokenizeCustomerInterface $customer = null): self
    {
        $this->setData(self::CUSTOMER, $customer);
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
