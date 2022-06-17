<?php
namespace Sezzle\Sezzlepay\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator as AbstractPaymentValidator;

/**
 * Class AbstractValidator
 */
abstract class AbstractValidator extends AbstractPaymentValidator
{

    const AMOUNT = 'amount';

    /**
     * Validate total amount
     *
     * @param array $response
     * @param float $amount
     * @return bool
     */
    public function validateTotalAmount(array $response, float $amount): bool
    {
        if ($amount <= 0) {
            return false;
        }

        return isset($response[self::AMOUNT])
            && ($response[self::AMOUNT]) === $amount;
    }
}
