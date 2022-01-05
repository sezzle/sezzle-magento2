<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */
namespace Sezzle\Sezzlepay\Api;

use Magento\Framework\Exception\IntegrationException;

/**
 * Interface WidgetInterface
 * @package Sezzle\Sezzlepay\Api
 */
interface WidgetInterface
{

    /**
     * Add to widget queue
     *
     * @return bool
     * @throws IntegrationException
     */
    public function addToWidgetQueue();
}
