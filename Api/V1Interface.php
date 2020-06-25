<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Payment
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Payment\Api;

use Magento\Framework\Exception\LocalizedException;
use Sezzle\Payment\Api\Data\AuthInterface;

interface V1Interface
{
    /**
     * Authenticate user
     *
     * @return AuthInterface
     * @throws LocalizedException
     */
    public function authenticate();

    /**
     * Create Sezzle Checkout Session
     *
     * @param string $merchantUUID
     * @param string $log
     * @return bool
     * @throws LocalizedException
     */
    public function sendLogsToSezzle($merchantUUID, $log);
}
