<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Payment
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Payment\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Sezzle\Payment\Helper\Data;
use Sezzle\Payment\Model\Sezzle;

/**
 * Class SetSezzleCaptureExpiryObserver
 * @package Sezzle\Payment\Observer
 */
class SetSezzleAuthExpiryObserver implements ObserverInterface
{

    /**
     * @var Sezzle
    */
    private $sezzleModel;

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
     * @param Sezzle $sezzleModel
     * @param Data $sezzleHelper
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        Sezzle $sezzleModel,
        Data $sezzleHelper,
        ManagerInterface $messageManager
    ) {
        $this->sezzleModel = $sezzleModel;
        $this->sezzleHelper = $sezzleHelper;
        $this->messageManager = $messageManager;
    }

    /**
     * Set Sezzle Capture Expiry for Authorize Only payment action
     *
     * @param Observer $observer
     * @return SetSezzleAuthExpiryObserver
     * @throws Exception
     */
    public function execute(Observer $observer)
    {
        try {
            /** @var OrderInterface $order */
            $order = $observer->getEvent()->getOrder();
            if ($order->getPayment()->getMethod() != Sezzle::PAYMENT_CODE) {
                return $this;
            }
            $this->sezzleHelper->logSezzleActions('****Sezzle capture time setting start****');
            $paymentAction = $order->getPayment()->getAdditionalInformation('payment_type');
            $this->sezzleHelper->logSezzleActions("Payment Type : $paymentAction");
            switch ($paymentAction) {
                case sezzle::ACTION_AUTHORIZE:
                    $this->sezzleModel->setSezzleAuthExpiry($order);
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
