<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Api\Data;

/**
 * Interface LinkInterface
 * @package Sezzle\Sezzlepay\Api\Data
 */
interface LinkInterface
{
    public const HREF = 'href';
    public const REL = "rel";
    public const METHOD = "method";

    /**
     * @return string
     */
    public function getHref(): string;

    /**
     * @param string $href
     * @return $this
     */
    public function setHref(string $href): self;

    /**
     * @return string
     */
    public function getRel(): string;

    /**
     * @param string $rel
     * @return $this
     */
    public function setRel(string $rel): self;

    /**
     * @return string
     */
    public function getMethod(): string;

    /**
     * @param string $method
     * @return $this
     */
    public function setMethod(string $method): self;
}
