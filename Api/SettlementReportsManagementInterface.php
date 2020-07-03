<?php


namespace Sezzle\Sezzlepay\Api;


use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

interface SettlementReportsManagementInterface
{

    /**
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function syncAndSave();

}
