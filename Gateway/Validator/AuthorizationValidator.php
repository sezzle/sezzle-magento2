<?php

namespace Sezzle\Sezzlepay\Gateway\Validator;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\ResultInterface;

/**
 * AuthorizationValidator
 */
class AuthorizationValidator extends AbstractValidator {

    /**
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject): ResultInterface
    {
        $response = SubjectReader::readResponse($validationSubject);
        $amount = SubjectReader::readAmount($validationSubject);

        $fails = [];
        $isValid = true;
        if ($this->validateTotalAmount($response, $amount)) {
            $isValid = false;
            $fails[] = __("Amount cannot be less than or equal to 0.");
        }

        return $this->createResult($isValid, $fails);
    }
}
