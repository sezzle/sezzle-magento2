<?php

namespace Sezzle\Sezzlepay\Gateway\Request\Session;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order\Payment;
use Sezzle\Sezzlepay\Gateway\Command\AuthorizeCommand;
use Sezzle\Sezzlepay\Gateway\Config\Config;
use Sezzle\Sezzlepay\Gateway\Response\ReauthorizeOrderHandler;
use Sezzle\Sezzlepay\Helper\Util;


/**
 * CancelUrlRequestBuilder
 */
class CancelUrlRequestBuilder implements BuilderInterface
{

    /**
     * @var Config
     */
    private $config;

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function build(array $buildSubject): array
    {
        return [
            'cancel_url' => [
                'href' => $this->config->getCancelURL()
            ]
        ];
    }
}
