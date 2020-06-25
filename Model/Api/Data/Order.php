<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model\Api\Data;


use Magento\Framework\Api\AbstractExtensibleObject;
use Sezzle\Sezzlepay\Api\Data\AmountInterface;
use Sezzle\Sezzlepay\Api\Data\AuthorizationInterface;
use Sezzle\Sezzlepay\Api\Data\CustomerInterface;
use Sezzle\Sezzlepay\Api\Data\OrderInterface;

class Order extends AbstractExtensibleObject implements OrderInterface
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
    public function getIntent()
    {
        return $this->_get(self::INTENT);
    }

    /**
     * @inheritDoc
     */
    public function setIntent($intent)
    {
        $this->setData(self::INTENT, $intent);
    }

    /**
     * @inheritDoc
     */
    public function getReferenceID()
    {
        return $this->_get(self::REFERENCE_ID);
    }

    /**
     * @inheritDoc
     */
    public function setReferenceID($referenceID)
    {
        $this->setData(self::REFERENCE_ID, $referenceID);
    }

    /**
     * @inheritDoc
     */
    public function getDescription()
    {
        return $this->_get(self::DESCRIPTION);
    }

    /**
     * @inheritDoc
     */
    public function setDescription($description)
    {
        $this->setData(self::DESCRIPTION, $description);
    }

    /**
     * @inheritDoc
     */
    public function getOrderAmount()
    {
        return $this->_get(self::ORDER_AMOUNT);
    }

    /**
     * @inheritDoc
     */
    public function setOrderAmount(AmountInterface $orderAmount = null)
    {
        $this->setData(self::ORDER_AMOUNT, $orderAmount);
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
    public function setCustomer(CustomerInterface $customer = null)
    {
        $this->setData(self::CUSTOMER, $customer);
    }

    /**
     * @inheritDoc
     */
    public function getAuthorization()
    {
        return $this->_get(self::AUTHORIZATION);
    }

    /**
     * @inheritDoc
     */
    public function setAuthorization(AuthorizationInterface $authorization = null)
    {
        $this->setData(self::AUTHORIZATION, $authorization);
    }
}
