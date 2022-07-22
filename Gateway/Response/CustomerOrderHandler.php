<?php

namespace Sezzle\Sezzlepay\Gateway\Response;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Payment;
use Sezzle\Sezzlepay\Gateway\Command\AuthorizeCommand;

/**
 * CustomerOrderHandler
 */
class CustomerOrderHandler implements HandlerInterface
{

    const KEY_GET_ORDER_LINK = 'sezzle_get_order_link';

    /**
     * @param array $handlingSubject
     * @param array $response
     * @return void
     * @throws LocalizedException
     */
    public function handle(array $handlingSubject, array $response): void
    {
        $paymentDO = SubjectReader::readPayment($handlingSubject);

        /** @var Payment $payment */
        $payment = $paymentDO->getPayment();

        $payment->setAdditionalInformation(AuthorizeCommand::KEY_ORIGINAL_ORDER_UUID, $response['uuid']);

        $hateOSLinks = $response['links'];
        foreach ($hateOSLinks as $link) {
            $rel = 'sezzle_' . $link['rel'] . '_link';
            if ($link['method'] == 'GET' && strpos($rel, 'self') !== false) {
                $rel = self::KEY_GET_ORDER_LINK;
            }
            $payment->setAdditionalInformation($rel, $link['href']);
        }
    }
}
