<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Controller\Adminhtml\Widget;

use Laminas\Http\Response;
use Magento\Backend\App\Action;
use Magento\Framework\App\Cache\Frontend\Pool;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Sezzle\Sezzlepay\Api\V2Interface;
use Sezzle\Sezzlepay\Gateway\Config\Config;
use Sezzle\Sezzlepay\Model\Ui\ConfigProvider;

/**
 * Class Queue
 * @package Sezzle\Sezzlepay\Controller\Adminhtml\Widget
 */
class Queue extends Action
{
    const ADMIN_RESOURCE = 'Magento_Config::config';

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
     * @var DateTime
     */
    private $dateTime;
    /**
     * @var TypeListInterface
     */
    private $cacheTypeList;
    /**
     * @var Pool
     */
    private $cacheFrontendPool;

    /**
     * Queue constructor.
     * @param Action\Context $context
     * @param FormKeyValidator $formKeyValidator
     * @param RawFactory $rawResultFactory
     * @param V2Interface $v2
     * @param WriterInterface $configWriter
     * @param DateTime $dateTime
     * @param TypeListInterface $cacheTypeList
     * @param Pool $cacheFrontendPool
     */
    public function __construct(
        Action\Context    $context,
        FormKeyValidator  $formKeyValidator,
        RawFactory        $rawResultFactory,
        V2Interface       $v2,
        WriterInterface   $configWriter,
        DateTime          $dateTime,
        TypeListInterface $cacheTypeList,
        Pool              $cacheFrontendPool
    )
    {
        parent::__construct($context);
        $this->formKeyValidator = $formKeyValidator;
        $this->rawResultFactory = $rawResultFactory;
        $this->v2 = $v2;
        $this->configWriter = $configWriter;
        $this->dateTime = $dateTime;
        $this->cacheTypeList = $cacheTypeList;
        $this->cacheFrontendPool = $cacheFrontendPool;
    }

    /**
     * Check if request is allowed.
     *
     * @return bool
     */
    private function isRequestAllowed(): bool
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
            $response->setHttpResponseCode(Response::STATUS_CODE_404);
            return $response;
        }

        try {
            $this->v2->addToWidgetQueue();
            $currentTimestamp = $this->dateTime->date();
            $this->configWriter->save(
                sprintf('payment/%s/%s', ConfigProvider::CODE, Config::KEY_WIDGET_TICKET_CREATED_AT),
                $currentTimestamp
            );

            $this->cacheTypeList->cleanType('config');
            foreach ($this->cacheFrontendPool as $cacheFrontend) {
                $cacheFrontend->getBackend()->clean();
            }

            $response->setHttpResponseCode(Response::STATUS_CODE_204);
        } catch (LocalizedException $e) {
            $response->setHttpResponseCode(Response::STATUS_CODE_500);
        }

        return $response;
    }
}
