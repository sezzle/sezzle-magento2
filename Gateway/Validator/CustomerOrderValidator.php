<?php

declare(strict_types=1);

namespace Sezzle\Sezzlepay\Gateway\Validator;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\AbstractValidator;

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

        if (!isset($response['authorization']) || !$response['authorization']) {
            return $this->createResult(false, [__('Order is not authorized̵ by Sezzle.')]);
        }

        return $this->createResult(true);
    }
}
