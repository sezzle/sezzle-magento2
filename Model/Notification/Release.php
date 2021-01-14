<?php

namespace Sezzle\Sezzlepay\Model\Notification;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Json\Helper\Data;
use Magento\Framework\Notification\MessageInterface;
use Sezzle\Sezzlepay\Model\Api\ProcessorInterface;

class Release implements MessageInterface
{
    /**
     * @var ProcessorInterface
     */
    private $processor;
    /**
     * @var Data
     */
    private $jsonHelper;
    /**
     * @var \Sezzle\Sezzlepay\Helper\Data
     */
    private $sezzleHelper;

    public function __construct(
        Data $jsonHelper,
        ProcessorInterface $processor,
        \Sezzle\Sezzlepay\Helper\Data $sezzleHelper
    ) {
        $this->processor = $processor;
        $this->jsonHelper = $jsonHelper;
        $this->sezzleHelper = $sezzleHelper;
    }

    public function getIdentity()
    {
        // Retrieve unique message identity
        return 'identity';
    }

    public function isDisplayed()
    {
        // Return true to show your message, false to hide it
        return true;
    }

    public function getText()
    {
        // message text

        try {
//            $jData = "";
//            $data = $this->processor->call(
//                "https://raw.githubusercontent.com/sezzle/sezzle-magento2/master/composer.json"
//            );
//            $jData = $this->jsonHelper->jsonDecode($data);
//            $this->sezzleHelper->logSezzleActions($jData);
//            $jsonurl = "https://raw.githubusercontent.com/sezzle/sezzle-magento2/master/composer.json";
//            $json = file_get_contents($jsonurl);
//            $this->sezzleHelper->logSezzleActions($json);
        } catch (LocalizedException $e) {
        }

        return "abcd";
    }

    public function getSeverity()
    {
        return self::SEVERITY_NOTICE;
    }
}
