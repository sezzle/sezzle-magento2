<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Api\Data;

/**
 * Interface SessionInterface
 * @package Sezzle\Sezzlepay\Api\Data
 */
interface SessionInterface
{
    public const UUID = "uuid";
    public const ORDER = "order";
    public const TOKENIZE = "tokenize";

    /**
     * @return string|null
     */
    public function getUuid(): ?string;

    /**
     * @param string $uuid
     * @return $this
     */
    public function setUuid(string $uuid): self;

    /**
     * @return \Sezzle\Sezzlepay\Api\Data\SessionOrderInterface|null
     */
    public function getOrder(): ?SessionOrderInterface;

    /**
     * @param \Sezzle\Sezzlepay\Api\Data\SessionOrderInterface $sessionOrder
     * @return $this
     */
    public function setOrder(?SessionOrderInterface $sessionOrder = null): self;

    /**
     * @return \Sezzle\Sezzlepay\Api\Data\SessionTokenizeInterface|null
     */
    public function getTokenize(): ?SessionTokenizeInterface;

    /**
     * @param \Sezzle\Sezzlepay\Api\Data\SessionTokenizeInterface $sessionTokenize
     * @return $this
     */
    public function setTokenize(?SessionTokenizeInterface $sessionTokenize = null): self;
}
