<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model\Config\Container;

/**
 * Interface CartWidgetConfigInterface
 * @package Sezzle\Sezzlepay\Model\Config\Container
 */
interface CartWidgetConfigInterface extends IdentityInterface
{

    /**
     * Get Target Xpath
     * @return mixed
     */
    public function getTargetXPath();

    /**
     * Get Render to Path
     * @return mixed
     */
    public function getRenderToPath();

    /**
     * Get forced show
     * @return mixed
     */
    public function getForcedShow();

    /**
     * Get alignment
     * @return mixed
     */
    public function getAlignment();

    /**
     * Get theme
     * @return mixed
     */
    public function getTheme();

    /**
     * Get width type
     * @return mixed
     */
    public function getWidthType();

    /**
     * Get image url
     * @return mixed
     */
    public function getImageUrl();

    /**
     * Get hide class
     * @return mixed
     */
    public function getHideClass();
}
