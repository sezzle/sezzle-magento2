<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Plugin\Sales\Controller\Adminhtml\System\Config;

use Closure;
use Exception;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Config\Model\Config;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Config\Controller\Adminhtml\System\Config\Save;
use Sezzle\Sezzlepay\Gateway\Http\AuthenticationService;
use Sezzle\Sezzlepay\Helper\Data;
use Sezzle\Sezzlepay\Model\Ui\ConfigProvider;

/**
 * Class SavePlugin
 * @package Sezzle\Sezzlepay\Plugin\Sales\Controller\Adminhtml\System\Config
 */
class SavePlugin
{
    /**
     * @var ManagerInterface
     */
    private $messageManager;
    /**
     * @var RedirectFactory
     */
    private $resultRedirectFactory;

    /**
     * @var Data
     */
    private $sezzleHelper;

    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var AuthenticationService
     */
    private $authenticationService;

    /**
     * SavePlugin constructor.
     * @param ManagerInterface $messageManager
     * @param RedirectFactory $resultRedirectFactory
     * @param Data $sezzleHelper
     */
    public function __construct(
        ManagerInterface $messageManager,
        RedirectFactory  $resultRedirectFactory,
        Data             $sezzleHelper,
        RequestInterface $request,
        Config           $config,
        AuthenticationService $authenticationService
    )
    {
        $this->messageManager = $messageManager;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->sezzleHelper = $sezzleHelper;
        $this->request = $request;
        $this->config = $config;
        $this->authenticationService = $authenticationService;
    }

    /**
     * Capture case check, if offline mode, don't allow
     *
     * @param Save $subject
     * @param Closure $proceed
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function aroundExecute(Save $subject, Closure $proceed): \Magento\Framework\Controller\Result\Redirect
    {
        $groups = $this->request->getPost('groups');

        $old = [
            'public_key' => $this->config->getConfigDataValue(''),
            'private_key' => $this->config->getConfigDataValue(''),
            'payment_mode' => $this->config->getConfigDataValue(''),
        ];

        $fields = $groups[ConfigProvider::CODE]['groups']['sezzle_payment']['fields'];

        $new = [
            'public_key' => $fields['public_key']['inherit'] ? $old['public_key'] : (string)$fields['public_key']['value'],
            'private_key' => $fields['private_key']['inherit'] ? $old['private_key'] : (string)$fields['private_key']['value'],
            'payment_mode' => $fields['payment_mode']['inherit'] ? $old['payment_mode'] : (string)$fields['payment_mode']['value'],
        ];

        if ($old === $new) {
            return $proceed();
        }

        try {
            if (!$this->authenticationService->validateAPIKeys(
                $new['public_key'],
                $new['private_key'],
                $new['payment_mode']
            )) {
                throw new LocalizedException(__('Unable to validate API keys'));
            }
        } catch (LocalizedException $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __('Something went wrong while saving this configuration:') . ' ' . $e->getMessage()
            );

            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath(
                'adminhtml/system_config/edit',
                [
                    '_current' => ['section', 'website', 'store'],
                    '_nosid' => true
                ]
            );
        }

        return $proceed();


    }
}
