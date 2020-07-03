<?php


namespace Sezzle\Sezzlepay\Model;


use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractExtensibleModel;
use Sezzle\Sezzlepay\Api\Data\SettlementReportsInterface;

class SettlementReports extends AbstractExtensibleModel implements IdentityInterface, SettlementReportsInterface
{

    const CACHE_TAG = 'sezzle_settlement_reports';

    protected $_cacheTag = 'sezzle_settlement_reports';
    protected $_eventPrefix = 'sezzle_settlement_reports';

    /**
     * @var string
     */
    protected $_eventObject = 'settlement_reports';

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

    public function getUuid()
    {
        return $this->_getData(self::UUID);
    }

    public function setUuid($uuid)
    {
        $this->setData(self::UUID, $uuid);
    }

    public function getPayoutCurrency()
    {
        return $this->_getData(self::PAYOUT_CURRENCY);
    }

    public function setPayoutCurrency($payoutCurrency)
    {
        $this->setData(self::PAYOUT_CURRENCY, $payoutCurrency);
    }

    public function getPayoutDate()
    {
        return $this->_getData(self::PAYOUT_DATE);
    }

    public function setPayoutDate($payoutDate)
    {
        $this->setData(self::PAYOUT_DATE, $payoutDate);
    }

    public function getNetSettlementAmount()
    {
        return $this->_getData(self::NET_SETTLEMENT_AMOUNT);
    }

    public function setNetSettlementAmount($netSettlementAmount)
    {
        $this->setData(self::NET_SETTLEMENT_AMOUNT, $netSettlementAmount);
    }

    public function getForexFees()
    {
        return $this->_getData(self::FOREX_FEES);
    }

    public function setForexFees($forexFees)
    {
        $this->setData(self::FOREX_FEES, $forexFees);
    }

    public function getStatus()
    {
        return $this->_getData(self::STATUS);
    }

    public function setStatus($status)
    {
        $this->setData(self::STATUS, $status);
    }
}
