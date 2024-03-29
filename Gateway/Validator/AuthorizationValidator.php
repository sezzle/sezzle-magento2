<?php

namespace Sezzle\Sezzlepay\Gateway\Validator;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Payment\Model\MethodInterface;
use Magento\Payment\Gateway\Validator\AbstractValidator;

/**
 * AuthorizationValidator
 */
class AuthorizationValidator extends AbstractValidator
{

    const KEY_AUTH_EXPIRY = 'sezzle_auth_expiry';

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @param ResultInterfaceFactory $resultFactory
     * @param DateTime $dateTime
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory,
        DateTime               $dateTime
    )
    {
        parent::__construct($resultFactory);
        $this->dateTime = $dateTime;
    }

    /**
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject): ResultInterface
    {
        $paymentDO = SubjectReader::readPayment($validationSubject);

        $payment = $paymentDO->getPayment();

        if ($payment->getAdditionalInformation('payment_type') !== MethodInterface::ACTION_AUTHORIZE) {
            return $this->createResult(true);
        }

        $currentTimestamp = $this->dateTime->timestamp('now');
        $authExpiry = $payment->getAdditionalInformation(self::KEY_AUTH_EXPIRY);
        $expirationTimestamp = $this->dateTime->timestamp($authExpiry);
        if ($expirationTimestamp < $currentTimestamp) {
            return $this->createResult(false, [__('Authorization expired.')]);
        }

        return $this->createResult(true);
    }
}
