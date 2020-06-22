<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Payment
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Payment\Api\Data;

/**
 * Interface SessionInterface
 * @package Sezzle\Payment\Api\Data
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
     * @return \Sezzle\Payment\Api\Data\SessionOrderInterface|null
     */
    public function getOrder();

    /**
     * @param \Sezzle\Payment\Api\Data\SessionOrderInterface $sessionOrder
     * @return $this
     */
    public function setOrder(SessionOrderInterface $sessionOrder = null);

    /**
     * @return \Sezzle\Payment\Api\Data\SessionTokenizeInterface|null
     */
    public function getTokenize();

    /**
     * @param \Sezzle\Payment\Api\Data\SessionTokenizeInterface $sessionTokenize
     * @return mixed
     */
    public function setTokenize(SessionTokenizeInterface $sessionTokenize = null);
}
