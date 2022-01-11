<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Controller\Adminhtml\Widget;

use Magento\Backend\App\Action;
use Magento\Config\Model\ResourceModel\Config as ResourceConfig;
use Magento\Framework\App\Cache\Frontend\Pool;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Response\Http;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use PayPal\Braintree\Gateway\Config\Config;
use Sezzle\Sezzlepay\Api\V2Interface;
use Sezzle\Sezzlepay\Model\System\Config\Container\SezzleIdentity;

/**
 * Class Queue
 * @package Sezzle\Sezzlepay\Controller\Adminhtml\Widget
 */
class Queue extends Action
{
    const ADMIN_RESOURCE = 'Magento_Config::config';

    /**
     * @var Config
     */
    protected $config;
    /**
     * @var FormKeyValidator
     */
    private $formKeyValidator;
    /**
     * @var RawFactory
     */
    private $rawResultFactory;
    /**
     * @var V2Interface
     */
    private $v2;
    /**
     * @var WriterInterface
     */
    private $configWriter;
    /**
     * @var ResourceConfig
     */
    private $resourceConfig;
    /**
     * @var DateTime
     */
    private $dateTime;
    /**
     * @var TypeListInterface
     */
    private TypeListInterface $cacheTypeList;
    /**
     * @var Pool
     */
    private Pool $cacheFrontendPool;

    /**
     * Queue constructor.
     * @param Action\Context $context
     * @param Config $config
     * @param FormKeyValidator $formKeyValidator
     * @param RawFactory $rawResultFactory
     * @param V2Interface $v2
     * @param WriterInterface $configWriter
     * @param ResourceConfig $resourceConfig
     * @param DateTime $dateTime
     * @param TypeListInterface $cacheTypeList
     * @param Pool $cacheFrontendPool
     */
    public function __construct(
        Action\Context $context,
        Config $config,
        FormKeyValidator $formKeyValidator,
        RawFactory $rawResultFactory,
        V2Interface $v2,
        WriterInterface $configWriter,
        ResourceConfig $resourceConfig,
        DateTime $dateTime,
        TypeListInterface $cacheTypeList,
        Pool $cacheFrontendPool
    ) {
        parent::__construct($context);
        $this->config = $config;
        $this->formKeyValidator = $formKeyValidator;
        $this->rawResultFactory = $rawResultFactory;
        $this->v2 = $v2;
        $this->configWriter = $configWriter;
        $this->resourceConfig = $resourceConfig;
        $this->dateTime = $dateTime;
        $this->cacheTypeList = $cacheTypeList;
        $this->cacheFrontendPool = $cacheFrontendPool;
    }

    /**
     * Check if request is allowed.
     *
     * @return bool
     */
    private function isRequestAllowed()
    {
        return $this->getRequest()->isAjax() && $this->getRequest()->isPost();
    }

    /**
     * @return Raw
     */
    public function execute(): Raw
    {
        $response = $this->rawResultFactory->create();
        if (!$this->isRequestAllowed() || !$this->formKeyValidator->validate($this->getRequest())) {
            $response->setHttpResponseCode(Http::STATUS_CODE_404);
            return $response;
        }

        try {
            $this->v2->addToWidgetQueue();
            $currentTimestamp = $this->dateTime->date();
            $this->configWriter->save(SezzleIdentity::XML_PATH_WIDGET_TICKET_CREATED_AT, $currentTimestamp);

            $this->cacheTypeList->cleanType('config');
            foreach ($this->cacheFrontendPool as $cacheFrontend) {
                $cacheFrontend->getBackend()->clean();
            }

            $response->setHttpResponseCode(Http::STATUS_CODE_204);
        } catch (LocalizedException $e) {
            $response->setHttpResponseCode(Http::STATUS_CODE_500);
        }

        return $response;
    }
}
