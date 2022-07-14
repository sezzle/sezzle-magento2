<?php

namespace Sezzle\Sezzlepay\Plugin;

use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Sales\Model\Order\Invoice as Subject;
use Sezzle\Sezzlepay\Model\Ui\ConfigProvider;

class ReauthorizeOrder
{

    /**
     * @var CommandInterface
     */
    private $reauthOrderCommand;

    public function __construct(CommandInterface $reauthOrderCommand)
    {
        $this->reauthOrderCommand = $reauthOrderCommand;
    }

    public function beforeCapture(Subject $subject)
    {
        $order = $subject->getOrder();
        if ($order->getPayment()->getMethod() !== ConfigProvider::CODE) {
            return null;
        }

        $baseTotalPaid = $subject->getBaseGrandTotal();
        $invoiceList = $order->getInvoiceCollection();
        // calculate all totals
        if (count($invoiceList->getItems()) > 1) {
            $baseTotalPaid += $order->getBaseTotalPaid();
        }

        try {
            $this->reauthOrderCommand->execute(
                [
                    'payment' => $subject->getOrder()->getPayment(),
                    'amount' => $baseTotalPaid
                ]
            );
        } catch (CommandException $e) {
        }


        return null;
    }

}
