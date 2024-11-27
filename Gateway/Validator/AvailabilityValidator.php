<?php

namespace Sezzle\Sezzlepay\Gateway\Validator;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Magento\Quote\Model\Quote;
use Sezzle\Sezzlepay\Gateway\Config\Config;
use Sezzle\Sezzlepay\Helper\Data;

/**
 * AvailabilityValidator
 */
class AvailabilityValidator extends AbstractValidator
{

    /**
     * @var Config
     */
    private $config;

    /**
     * AvailabilityValidator constructor
     * @param ResultInterfaceFactory $resultFactory
     * @param Config $config
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory,
        Config                 $config
    )
    {
        $this->config = $config;
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
        // Ensure 'quote' key exists and is valid
        if (!isset($validationSubject['quote']) || !$validationSubject['quote'] instanceof Quote) {
            $this->helper->logSezzleActions('Validation failed: Missing or invalid quote in validationSubject.');
            return $this->createResult(false, [__('Invalid or missing quote data.')]);
        }

        /** @var Quote $quote */
        $quote = $validationSubject['quote'];

        $merchantUUID = $this->config->getMerchantUUID();
        $publicKey = $this->config->getPublicKey();
        $privateKey = $this->config->getPrivateKey();
        $minCheckoutAmount = $this->config->getMinCheckoutAmount();

        switch (true) {
            case (!$merchantUUID || !$publicKey || !$privateKey):
                $this->helper->logSezzleActions('Validation failed: Missing Sezzle API keys.');
                return $this->createResult(false, [__('Sezzle API Keys are required.')]);
            case ($quote && ($quote->getBaseGrandTotal() < $minCheckoutAmount)):
                $this->helper->logSezzleActions(sprintf(
                    'Validation failed: Order total %.2f is less than minimum required amount %.2f.',
                    $quote->getBaseGrandTotal(),
                    $minCheckoutAmount
                ));
                return $this->createResult(false, [__(sprintf('Minimum order amount is %d.', $minCheckoutAmount))]);
        }

        return $this->createResult(true);
    }
}
