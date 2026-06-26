<?php

namespace Sezzle\Sezzlepay\Gateway\Response;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Model\Order\Payment;
use Sezzle\Sezzlepay\Gateway\Command\AuthorizeCommand;
use Sezzle\Sezzlepay\Gateway\Validator\AuthorizationValidator;

/**
 * OrderHandler
 */
class OrderHandler implements HandlerInterface
{

    /**
     * @param array $handlingSubject
     * @param array $response
     * @return void
     * @throws LocalizedException
     */
    public function handle(array $handlingSubject, array $response): void
    {
        $paymentDO = SubjectReader::readPayment($handlingSubject);

        /** @var Payment $payment */
        $payment = $paymentDO->getPayment();

        // Read the configured payment action from the payment's own method instance rather
        // than a bare injected adapter: getMethodInstance() binds the info instance, which
        // plugins on getConfigPaymentAction() (e.g. Braintree's) dereference without a null
        // check. A shared facade with no info instance would otherwise fatal here.
        if ($payment->getMethodInstance()->getConfigPaymentAction() === MethodInterface::ACTION_AUTHORIZE) {
            $payment->setAdditionalInformation(AuthorizationValidator::KEY_AUTH_EXPIRY, $response['authorization']['expiration']);
        }
    }
}
