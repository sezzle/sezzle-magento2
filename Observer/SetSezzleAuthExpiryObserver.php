<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Sezzle\Sezzlepay\Helper\Data;
use Sezzle\Sezzlepay\Model\SezzlePay;

/**
 * Class SetSezzleCaptureExpiryObserver
 * @package Sezzle\Sezzlepay\Observer
 */
class SetSezzleAuthExpiryObserver implements ObserverInterface
{
    const PAYMENT_CODE = 'sezzlepay';

    /**
     * @var SezzlePay
    */
    private $sezzlePayModel;

    /**
     * @var Data
    */
    private $sezzleHelper;

    /**
     * @var ManagerInterface
    */
    private $messageManager;

    /**
     * Construct
     *
     * @param SezzlePay $sezzlePayModel
     * @param Data $sezzleHelper
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        SezzlePay $sezzlePayModel,
        Data $sezzleHelper,
        ManagerInterface $messageManager
    ) {
        $this->sezzlePayModel = $sezzlePayModel;
        $this->sezzleHelper = $sezzleHelper;
        $this->messageManager = $messageManager;
    }

    /**
     * Set Sezzle Capture Expiry for Authorize Only payment action
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @throws Exception
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $this->sezzleHelper->logSezzleActions('****Sezzle capture time setting start****');
            /** @var OrderInterface $order */
            $order = $observer->getEvent()->getOrder();
            $paymentAction = $order->getPayment()->getAdditionalInformation('payment_type');
            $this->sezzleHelper->logSezzleActions("Payment Type : $paymentAction");
            switch ($paymentAction) {
                case SezzlePay::ACTION_AUTHORIZE:
                    $this->sezzlePayModel->setSezzleAuthExpiry($order);
                    $this->sezzleHelper->logSezzleActions('****Sezzle capture time setting end****');
                    break;
                default:
                    break;
            }
        } catch (\Exception $e) {
            $this->sezzleHelper->logSezzleActions('Unable to set capture time : ' . $e->getMessage());
            $this->messageManager->addExceptionMessage(
                $e,
                __('Unable to set capture time.')
            );
        }
    }
}
