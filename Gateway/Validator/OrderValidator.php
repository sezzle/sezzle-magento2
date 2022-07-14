<?php

namespace Sezzle\Sezzlepay\Gateway\Validator;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Sezzle\Sezzlepay\Gateway\Response\AuthorizationHandler;
use Sezzle\Sezzlepay\Gateway\Response\ReauthorizeOrderHandler;

/**
 * OrderValidator
 */
class OrderValidator extends AbstractValidator
{
    /**
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject): ResultInterface
    {
        $response = SubjectReader::readResponse($validationSubject);
        $paymentDO = SubjectReader::readPayment($validationSubject);

        /** @var OrderPaymentInterface $payment */
        $payment = $paymentDO->getPayment();

        $orderUUID = $payment->getAdditionalInformation(AuthorizationHandler::KEY_ORIGINAL_ORDER_UUID);

        $isValid = true;
        $fails = [];

        $statements = [
            [
                $orderUUID !== $response['uuid'],
                __('Order UUID doesn\'t match.')
            ],
            [
                !isset($response['authorization']) || !is_array($response['authorization']),
                __('Order is not authorized.'
                )
            ]
        ];

        foreach ($statements as $statementResult) {
            if ($statementResult[0]) {
                $isValid = false;
                $fails[] = $statementResult[1];
            }
        }

        return $this->createResult($isValid, $fails);
    }
}
