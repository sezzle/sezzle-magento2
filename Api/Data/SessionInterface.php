<?php


namespace Sezzle\Sezzlepay\Api\Data;

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
    public function getUuid();

    /**
     * @param $uuid
     * @return $this
     */
    public function setUuid($uuid);

    /**
     * @return \Sezzle\Sezzlepay\Api\Data\SessionOrderInterface|null
     */
    public function getOrder();

    /**
     * @param \Sezzle\Sezzlepay\Api\Data\SessionOrderInterface $sessionOrder
     * @return $this
     */
    public function setOrder(SessionOrderInterface $sessionOrder = null);

    /**
     * @return \Sezzle\Sezzlepay\Api\Data\SessionTokenizeInterface|null
     */
    public function getTokenize();

    /**
     * @param \Sezzle\Sezzlepay\Api\Data\SessionTokenizeInterface $sessionTokenize
     * @return mixed
     */
    public function setTokenize(SessionTokenizeInterface $sessionTokenize = null);
}
