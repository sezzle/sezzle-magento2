<?php


namespace Sezzle\Sezzlepay\Model\Api\Data;

use Magento\Framework\Api\AbstractExtensibleObject;
use Sezzle\Sezzlepay\Api\Data\AuthInterface;

/**
 * Class Auth
 * @package Sezzle\Sezzlepay\Model\Api\Data
 */
class Auth extends AbstractExtensibleObject implements AuthInterface
{

    /**
     * @inheritDoc
     */
    public function getPublicKey()
    {
        return $this->_get(self::PUBLIC_KEY);
    }

    /**
     * @inheritDoc
     */
    public function setPublicKey($publicKey)
    {
        $this->setData(self::PUBLIC_KEY, $publicKey);
    }

    /**
     * @inheritDoc
     */
    public function getPrivateKey()
    {
        return $this->_get(self::PRIVATE_KEY);
    }

    /**
     * @inheritDoc
     */
    public function setPrivateKey($privateKey)
    {
        $this->setData(self::PUBLIC_KEY, $privateKey);
    }

    /**
     * @inheritDoc
     */
    public function getExpirationDate()
    {
        return $this->_get(self::EXPIRATION_DATE);
    }

    /**
     * @inheritDoc
     */
    public function setExpirationDate($expirationDate)
    {
        $this->setData(self::EXPIRATION_DATE, $expirationDate);
    }

    /**
     * @inheritDoc
     */
    public function getMerchantUUID()
    {
        return $this->_get(self::MERCHANT_UUID);
    }

    /**
     * @inheritDoc
     */
    public function setMerchantUUID($merchantUUID)
    {
        $this->setData(self::MERCHANT_UUID, $merchantUUID);
    }

    /**
     * @inheritDoc
     */
    public function getToken()
    {
        return $this->_get(self::TOKEN);
    }

    /**
     * @inheritDoc
     */
    public function setToken($token)
    {
        $this->setData(self::TOKEN, $token);
    }
}
