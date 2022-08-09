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
    public function sendLogsToSezzle(string $merchantUUID, string $log, int $storeId): bool;
}
