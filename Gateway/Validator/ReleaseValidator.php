<?php

namespace Sezzle\Sezzlepay\Gateway\Validator;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\ResultInterface;

/**
 * ReleaseValidator
 */
class ReleaseValidator extends AbstractValidator
{

    /**
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject): ResultInterface
    {
        $response = SubjectReader::readResponse($validationSubject);

        if (!isset($response["uuid"]) || !$response["uuid"]) {
            return $this->createResult(false, [__("Unable to release the amount.")]);
        }

        return $this->createResult(true);
    }
}
