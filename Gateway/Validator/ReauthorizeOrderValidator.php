<?php

namespace Sezzle\Sezzlepay\Gateway\Validator;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\AbstractValidator;

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

        if (!isset($response['authorization']['approved']) || !$response['authorization']['approved']) {
            return $this->createResult(false, [__('Reauthorization is not approved by Sezzle.')]);
        }

        return $this->createResult(true);
    }
}
