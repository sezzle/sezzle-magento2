<?php


namespace Sezzle\Sezzlepay\Api\Data;

use Magento\Setup\Test\Unit\Controller\SessionTest;
use Magento\Tests\NamingConvention\true\string;

/**
 * Interface SessionInterface
 * @package Sezzle\Sezzlepay\Api\Data
 */
interface SessionInterface
{
    const UUID = "uuid";
    const ORDER = "order";
    const TOKENIZE = "tokenize";

    /**
     * @return string|null
     */
    public function getUUID();

    /**
     * @param $uuid
     * @return $this
     */
    public function setUUID($uuid);

    /**
     * @return SessionOrderInterface|null
     */
    public function getOrder();

    /**
     * @param SessionOrderInterface $sessionOrder
     * @return $this
     */
    public function setOrder(SessionOrderInterface $sessionOrder);

    /**
     * @return SessionTokenizeInterface|null
     */
    public function getTokenize();

    /**
     * @param SessionTokenizeInterface $sessionTokenize
     * @return mixed
     */
    public function setTokenize(SessionTokenizeInterface $sessionTokenize);
}
