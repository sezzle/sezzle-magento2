<?php

namespace Sezzle\Sezzlepay\Gateway\Validator;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Sezzle\Sezzlepay\Gateway\Response\AuthorizationHandler;

/**
 * OrderValidator
 */
class OrderValidator extends AbstractValidator
{
    /**
     * Validate secret in url
     *
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject): ResultInterface
    {
        $response = SubjectReader::readResponse($validationSubject);
        $paymentDO = SubjectReader::readPayment($validationSubject);

        $orderUUID = $paymentDO->getPayment()->getAdditionalInformation(AuthorizationHandler::KEY_ORIGINAL_ORDER_UUID);

        $isValid = true;
        $fails = [];

        $statements = [
            [
                $orderUUID !== $response['uuid'],
                __('Order UUID doesn\'t match.')
            ],
            [
                !isset($response['authorization']) || !is_array($response['authorization']),
                __('Checkout is not authorized.'
                )
            ]
        ];

        foreach ($statements as $statementResult) {
            if (!$statementResult[0]) {
                $isValid = false;
                $fails[] = $statementResult[1];
            }
        }

        return $this->createResult($isValid, $fails);
    }
}
