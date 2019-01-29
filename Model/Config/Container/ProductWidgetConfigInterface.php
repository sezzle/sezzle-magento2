<?php

namespace Sezzle\Sezzlepay\Model\Config\Container;

use Magento\Store\Model\Store;

/**
 * Interface IdentityInterface
 * @package Sezzle\Sezzlepay\Model\Config\Container
 */
interface ProductWidgetConfigInterface extends IdentityInterface
{

    /**
     * @return mixed
     */
    public function getTargetXPath();

    /**
     * @return mixed
     */
    public function getRenderToPath();

    /**
     * @return mixed
     */
    public function getForcedShow();

    /**
     * @return mixed
     */
    public function getAlignment();

    /**
     * @return mixed
     */
    public function getTheme();

    /**
     * @return mixed
     */
    public function getWidthType();

    /**
     * @return mixed
     */
    public function getImageUrl();

    /**
     * @return mixed
     */
    public function getHideClass();
}
