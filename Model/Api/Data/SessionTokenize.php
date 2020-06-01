<?php


namespace Sezzle\Payment\Model\Api\Data;


use Magento\Framework\Api\AbstractExtensibleObject;
use Sezzle\Payment\Api\Data\SessionTokenizeInterface;
use Sezzle\Payment\Api\Data\TokenizeCustomerInterface;

class SessionTokenize extends AbstractExtensibleObject implements SessionTokenizeInterface
{

    /**
     * @inheritDoc
     */
    public function getStatus()
    {
        return $this->_get(self::STATUS);
    }

    /**
     * @inheritDoc
     */
    public function setStatus($status)
    {
        $this->setData(self::STATUS, $status);
    }

    /**
     * @inheritDoc
     */
    public function getToken()
    {
        return $this->_get(self::TOKEN);
    }

    /**
     * @inheritDoc
     */
    public function setToken($token)
    {
        $this->setData(self::TOKEN, $token);
    }

    /**
     * @inheritDoc
     */
    public function getApprovalUrl()
    {
        return $this->_get(self::APPROVAL_URL);
    }

    /**
     * @inheritDoc
     */
    public function setApprovalUrl($approvalURL)
    {
        $this->setData(self::APPROVAL_URL, $approvalURL);
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
    public function getCustomer()
    {
        return $this->_get(self::CUSTOMER);
    }

    /**
     * @inheritDoc
     */
    public function setCustomer(TokenizeCustomerInterface $customer = null)
    {
        $this->setData(self::CUSTOMER, $customer);
    }
}
