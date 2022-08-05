<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Api;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Sezzle\Sezzlepay\Api\Data\AuthorizationInterface;
use Sezzle\Sezzlepay\Api\Data\CustomerInterface;
use Sezzle\Sezzlepay\Api\Data\OrderInterface;
use Sezzle\Sezzlepay\Api\Data\SessionInterface;
use Sezzle\Sezzlepay\Api\Data\TokenizeCustomerInterface;

interface V2Interface
{
    /**
     * Create Sezzle Checkout Session
     *
     * @param string $reference
     * @param int $storeId
     * @return SessionInterface
     */
    public function createSession(string $reference, int $storeId): SessionInterface;

    /**
     * Get Customer by Customer UUID
     *
     * @param string $uri
     * @param string $customerUUID
     * @param int $storeId
     * @return CustomerInterface
     * @throws LocalizedException
     */
    public function getCustomer(string $uri, string $customerUUID, int $storeId): CustomerInterface;

    /**
     * Get Customer UUID by Session token
     *
     * @param string $uri
     * @param string $token
     * @param int $storeId
     * @return TokenizeCustomerInterface
     * @throws LocalizedException
     */
    public function getTokenDetails(string $uri, string $token, int $storeId): TokenizeCustomerInterface;

    /**
     * Get Settlement Report Summaries
     *
     * @param string|null $from
     * @param string|null $to
     * @return array|null
     * @throws LocalizedException
     * @throws Exception
     */
    public function getSettlementSummaries(string $from = null, string $to = null): ?array;

    /**
     * Get Settlement Report Details for a payout UUID
     *
     * @param string $payoutUUID
     * @return string|null
     * @throws LocalizedException
     */
    public function getSettlementDetails(string $payoutUUID): ?string;

    /**
     * Add request to widget queue
     *
     * @throws LocalizedException
     */
    public function addToWidgetQueue(): void;
}
