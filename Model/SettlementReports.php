<?php
/**
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */
namespace Sezzle\Sezzlepay\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractExtensibleModel;
use Sezzle\Sezzlepay\Api\Data\SettlementReportsInterface;

/**
 * Class SettlementReports
 * @package Sezzle\Sezzlepay\Model
 */
class SettlementReports extends AbstractExtensibleModel implements IdentityInterface, SettlementReportsInterface
{

    const CACHE_TAG = 'sezzle_settlement_reports';

    protected $_cacheTag = 'sezzle_settlement_reports';
    protected $_eventPrefix = 'sezzle_settlement_reports';
    protected $_eventObject = 'settlement_reports';

    /**
     * Constructor
     */
    protected function _construct()
    {
        $this->_init('Sezzle\Sezzlepay\Model\ResourceModel\SettlementReports');
    }

    /**
     * @inheritDoc
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * @inheritDoc
     */
    public function getUuid()
    {
        return $this->_getData(self::UUID);
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
    public function getPayoutCurrency()
    {
        return $this->_getData(self::PAYOUT_CURRENCY);
    }

    /**
     * @inheritDoc
     */
    public function setPayoutCurrency($payoutCurrency)
    {
        $this->setData(self::PAYOUT_CURRENCY, $payoutCurrency);
    }

    /**
     * @inheritDoc
     */
    public function getPayoutDate()
    {
        return $this->_getData(self::PAYOUT_DATE);
    }

    /**
     * @inheritDoc
     */
    public function setPayoutDate($payoutDate)
    {
        $this->setData(self::PAYOUT_DATE, $payoutDate);
    }

    /**
     * @inheritDoc
     */
    public function getNetSettlementAmount()
    {
        return $this->_getData(self::NET_SETTLEMENT_AMOUNT);
    }

    /**
     * @inheritDoc
     */
    public function setNetSettlementAmount($netSettlementAmount)
    {
        $this->setData(self::NET_SETTLEMENT_AMOUNT, $netSettlementAmount);
    }

    /**
     * @inheritDoc
     */
    public function getForexFees()
    {
        return $this->_getData(self::FOREX_FEES);
    }

    /**
     * @inheritDoc
     */
    public function setForexFees($forexFees)
    {
        $this->setData(self::FOREX_FEES, $forexFees);
    }

    /**
     * @inheritDoc
     */
    public function getStatus()
    {
        return $this->_getData(self::STATUS);
    }

    /**
     * @inheritDoc
     */
    public function setStatus($status)
    {
        $this->setData(self::STATUS, $status);
    }
}
