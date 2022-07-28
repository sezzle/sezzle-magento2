<?php

namespace Sezzle\Sezzlepay\Gateway\Response;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Model\Method\Adapter;
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
     * @var Adapter
     */
    private $adapter;

    /**
     * OrderHandler constructor
     *
     * @param Adapter $adapter
     */
    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }

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

        if ($this->adapter->getConfigPaymentAction() === MethodInterface::ACTION_AUTHORIZE) {
            $payment->setAdditionalInformation(AuthorizationValidator::KEY_AUTH_EXPIRY, $response['authorization']['expiration']);
        }
    }
}
