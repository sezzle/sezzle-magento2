<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Payment
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Payment\Api\Data;

/**
 * Interface LinkInterface
 * @package Sezzle\Payment\Api\Data
 */
interface LinkInterface
{
    const HREF = 'href';
    const REL = "rel";
    const METHOD = "method";

    /**
     * @return string
     */
    public function getHref();

    /**
     * @param string $href
     * @return $this
     */
    public function setHref($href);

    /**
     * @return string
     */
    public function getRel();

    /**
     * @param string $rel
     * @return $this
     */
    public function setRel($rel);

    /**
     * @return string
     */
    public function getMethod();

    /**
     * @param string $method
     * @return $this
     */
    public function setMethod($method);
}
