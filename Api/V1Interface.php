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
     * @return bool
     * @throws LocalizedException
     */
    public function sendLogsToSezzle($merchantUUID, $log);

    /**
     * @param string $orderReferenceID
     * @return bool
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function capture($orderReferenceID);

    /**
     * @param string $orderReferenceID
     * @param int $amount
     * @return string|null
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function refund($orderReferenceID, $amount);

    /**
     * @param string $orderReferenceID
     * @return OrderInterface
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getOrder($orderReferenceID);

}
