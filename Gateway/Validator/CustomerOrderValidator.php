<?php

namespace Sezzle\Sezzlepay\Gateway\Validator;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\ResultInterface;

/**
 * CustomerOrderValidator
 */
class CustomerOrderValidator extends AbstractValidator
{

    /**
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject): ResultInterface
    {
        $response = SubjectReader::readResponse($validationSubject);

        if (!isset($response["approved"]) || !$response["approved"]) {
            return $this->createResult(false, [__("Checkout is not approved by Sezzle.")]);
        }

        return $this->createResult(true);
    }
}
