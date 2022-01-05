<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model;

use Magento\Framework\Exception\IntegrationException;
use Magento\Framework\Exception\LocalizedException;
use Sezzle\Sezzlepay\Api\V2Interface;
use Sezzle\Sezzlepay\Api\WidgetInterface;

/**
 * Class Widget
 * @package Sezzle\Sezzlepay\Model
 */
class Widget implements WidgetInterface
{

    /**
     * @var V2Interface
     */
    private $v2;

    /**
     * Widget constructor.
     * @param V2Interface $v2
     */
    public function __construct(V2Interface $v2)
    {
        $this->v2 = $v2;
    }

    /**
     * @inheritDoc
     */
    public function addToWidgetQueue()
    {
        try {
            return $this->v2->addToWidgetQueue();
        } catch (LocalizedException $e) {
            throw new IntegrationException(
                __($e->getMessage()),
                $e
            );
        }

    }
}
