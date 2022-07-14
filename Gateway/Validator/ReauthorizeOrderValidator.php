<?php

namespace Sezzle\Sezzlepay\Gateway\Validator;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\ResultInterface;

/**
 * ReauthorizeOrderValidator
 */
class ReauthorizeOrderValidator extends AbstractValidator
{

    /**
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject): ResultInterface
    {
        $response = SubjectReader::readResponse($validationSubject);

        if (!isset($response["authorization"]) || !$response["authorization"]) {
            return $this->createResult(false, [__("Reauthorization is not approved by Sezzle.")]);
        }

        return $this->createResult(true);
    }
}
