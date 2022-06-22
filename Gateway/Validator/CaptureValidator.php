<?php

namespace Sezzle\Sezzlepay\Gateway\Validator;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\ResultInterface;

/**
 * CaptureValidator
 */
class CaptureValidator extends AbstractValidator {

    /**
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject): ResultInterface
    {
        $response = SubjectReader::readResponse($validationSubject);

        $fails = [];
        $isValid = true;
        if (!isset($response["uuid"]) || !$response["uuid"]) {
            $isValid = false;
            $fails[] = __("Unable to capture the amount.");
        }

        return $this->createResult($isValid, $fails);
    }
}
