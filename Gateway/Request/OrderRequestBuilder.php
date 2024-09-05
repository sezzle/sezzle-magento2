<?php

namespace Sezzle\Sezzlepay\Gateway\Request;

use Magento\Framework\Exception\NotFoundException;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Sezzle\Sezzlepay\Gateway\Command\AuthorizeCommand;

/**
 * OrderRequestBuilder
 */
class OrderRequestBuilder implements BuilderInterface
{

    /**
     * @inheritDoc
     * @throws NotFoundException
     */
    public function build(array $buildSubject): array
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);

        /** @var PaymentInterface $payment */
        $payment = $paymentDO->getPayment();

        $orderUUID = $payment->getAdditionalInformation(AuthorizeCommand::KEY_ORIGINAL_ORDER_UUID);
        if (!$orderUUID) {
            throw new NotFoundException(__('Order UUID not found.'));
        }

        return [
            '__store_id' => $payment->getQuote()->getStoreId(),
            '__route_params' => [
                'order_uuid' => $orderUUID
            ]
        ];
    }
}
