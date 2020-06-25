<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Api;

use Magento\Framework\Exception\LocalizedException;
use Sezzle\Sezzlepay\Api\Data\AuthInterface;

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
