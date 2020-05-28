<?php


namespace Sezzle\Sezzlepay\Model\Api\Data;


use Magento\Framework\Api\AbstractExtensibleObject;
use Sezzle\Sezzlepay\Api\Data\SessionTokenizeInterface;

class SessionTokenize extends AbstractExtensibleObject implements SessionTokenizeInterface
{

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
    public function getApprovalURL()
    {
        return $this->_get(self::APPROVAL_URL);
    }

    /**
     * @inheritDoc
     */
    public function setApprovalURL($approvalURL)
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
    public function setCustomer($customer)
    {
        $this->setData(self::CUSTOMER, $customer);
    }
}
