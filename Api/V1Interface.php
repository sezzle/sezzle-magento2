<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Api;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Sezzle\Sezzlepay\Api\Data\AuthInterface;
use Sezzle\Sezzlepay\Api\Data\OrderInterface;

interface V1Interface
{
    /**
     * Create Sezzle Checkout Session
     *
     * @param string $merchantUUID
     * @param string $log
     * @param int $storeId
     * @return bool
     */
    public function sendLogsToSezzle($merchantUUID, $log, $storeId);

    /**
     * @param string $orderReferenceID
     * @param int $storeId
     * @return bool
     */
    public function capture($orderReferenceID, $storeId);

    /**
     * @param string $orderReferenceID
     * @param int $amount
     * @param string $currency
     * @param int $storeId
     * @return string|null
     */
    public function refund($orderReferenceID, $amount, $currency, $storeId);

    /**
     * @param string $orderReferenceID
     * @param int $storeId
     * @return OrderInterface
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getOrder($orderReferenceID, $storeId);

}
