<?php

namespace Sezzle\Sezzlepay\Model\GraphQl\Resolver;

use Magento\Framework\Exception\InvalidArgumentException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Sezzle\Sezzlepay\Gateway\Config\Config;

/**
 * Validator
 */
class Validator
{

    /**
     * @var Config
     */
    private $config;

    /**
     * Validator constructor
     * @param Config $config
     */
    public function __construct(
        Config $config
    )
    {
        $this->config = $config;
    }

    /**
     * Validates the GraphQl input args
     * @param ContextInterface $context
     * @param array|null $args
     * @return void
     * @throws GraphQlInputException
     * @throws InputException
     * @throws InvalidArgumentException
     * @throws NoSuchEntityException
     */
    public function validateInput(ContextInterface $context, array $args = null)
    {
        $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();

        switch (true) {
            case !$this->config->isEnabled($storeId):
                throw new GraphQlInputException(__('Sezzle payment method is not enabled.'));
            case empty($args['input']['cart_id']):
                throw new InvalidArgumentException(__('Required parameter "cart_id" is missing.'));
        }
    }

}
