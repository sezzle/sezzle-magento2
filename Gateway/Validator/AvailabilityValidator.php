<?php

namespace Sezzle\Sezzlepay\Gateway\Validator;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Magento\Quote\Model\Quote;
use Sezzle\Sezzlepay\Gateway\Config\Config as SezzleConfig;

/**
 * AvailabilityValidator
 */
class AvailabilityValidator extends AbstractValidator
{

    /**
     * @var SezzleConfig
     */
    private $sezzleConfig;

    /**
     * AvailabilityValidator constructor
     * @param ResultInterfaceFactory $resultFactory
     * @param SezzleConfig $sezzleConfig
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory,
        SezzleConfig           $sezzleConfig)
    {
        $this->sezzleConfig = $sezzleConfig;
        parent::__construct($resultFactory);
    }

    /**
     * Validate for currency
     *
     * @param array $validationSubject
     * @return ResultInterface
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function validate(array $validationSubject): ResultInterface
    {
        /** @var Quote $quote */
        $quote = $validationSubject['quote'];

        $merchantID = $this->sezzleConfig->getMerchantID();
        $publicKey = $this->sezzleConfig->getPublicKey();
        $privateKey = $this->sezzleConfig->getPrivateKey();
        $minCheckoutAmount = $this->sezzleConfig->getMinCheckoutAmount();

        switch (true) {
            case (!$merchantID || !$publicKey || !$privateKey):
                return $this->createResult(false, [__('Sezzle API Keys are required.')]);
            case ($quote && ($quote->getBaseGrandTotal() < $minCheckoutAmount)):
                return $this->createResult(false, [__(sprintf('Minimum order amount is %d.', $minCheckoutAmount))]);
        }

        return $this->createResult(true);
    }
}
