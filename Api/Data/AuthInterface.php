<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Api\Data;

/**
 * Interface AuthInterface
 * @package Sezzle\Sezzlepay\Api\Data
 */
interface AuthInterface
{

    const PUBLIC_KEY = "public_key";
    const PRIVATE_KEY = "private_key";
    const EXPIRATION_DATE = "expiration_date";
    const MERCHANT_UUID = "merchant_uuid";
    const TOKEN = "token";

    /**
     * @return string|null
     */
    public function getPublicKey();

    /**
     * @param string $publicKey
     * @return $this
     */
    public function setPublicKey($publicKey);

    /**
     * @return string|null
     */
    public function getPrivateKey();

    /**
     * @param string $privateKey
     * @return $this
     */
    public function setPrivateKey($privateKey);

    /**
     * @return string|null
     */
    public function getExpirationDate();

    /**
     * @param string $expirationDate
     * @return $this
     */
    public function setExpirationDate($expirationDate);

    /**
     * @return string|null
     */
    public function getMerchantUuid();

    /**
     * @param string $merchantUUID
     * @return $this
     */
    public function setMerchantUuid($merchantUUID);

    /**
     * @return string|null
     */
    public function getToken();

    /**
     * @param string $token
     * @return $this
     */
    public function setToken($token);
}
