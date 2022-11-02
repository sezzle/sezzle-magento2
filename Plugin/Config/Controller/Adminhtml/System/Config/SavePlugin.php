<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Plugin\Config\Controller\Adminhtml\System\Config;

use Closure;
use Magento\Config\Model\Config;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Config\Controller\Adminhtml\System\Config\Save;
use Magento\Framework\Validation\ValidationException;
use Sezzle\Sezzlepay\Gateway\Http\AuthenticationService;
use Sezzle\Sezzlepay\Helper\Data;
use Sezzle\Sezzlepay\Model\Ui\ConfigProvider;
use Sezzle\Sezzlepay\Api\V2Interface;

/**
 * Class SavePlugin
 * @package Sezzle\Sezzlepay\Plugin\Config\Controller\Adminhtml\System\Config
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
    private $helper;

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
     * @var V2Interface
     */
    private $v2;

    /**
     * SavePlugin constructor.
     * @param ManagerInterface $messageManager
     * @param RedirectFactory $resultRedirectFactory
     * @param Data $helper
     * @param RequestInterface $request
     * @param Config $config
     * @param V2Interface $v2
     * @param AuthenticationService $authenticationService
     */
    public function __construct(
        ManagerInterface      $messageManager,
        RedirectFactory       $resultRedirectFactory,
        Data                  $helper,
        RequestInterface      $request,
        Config                $config,
        V2Interface           $v2,
        AuthenticationService $authenticationService
    )
    {
        $this->messageManager = $messageManager;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->helper = $helper;
        $this->request = $request;
        $this->config = $config;
        $this->v2 = $v2;
        $this->authenticationService = $authenticationService;
    }

    /**
     * Validate API Keys
     * Send Configuration Data
     *
     * @param Save $subject
     * @param Closure $proceed
     * @return Redirect
     */
    public function aroundExecute(Save $subject, Closure $proceed): Redirect
    {
        $groups = $this->request->getPost('groups');

        $old = [
            'public_key' => $this->config->getConfigDataValue($this->getPath('public_key')),
            'private_key' => $this->config->getConfigDataValue($this->getPath('private_key')),
            'payment_mode' => $this->config->getConfigDataValue($this->getPath('payment_mode')),
        ];

        $isSezzleConfig = isset($groups[ConfigProvider::CODE]) &&
            isset($groups[ConfigProvider::CODE]['groups']) &&
            isset($groups[ConfigProvider::CODE]['groups']['sezzle_payment']) &&
            isset($groups[ConfigProvider::CODE]['groups']['sezzle_payment']['fields']);
        if (!$isSezzleConfig) {
            $this->v2->sendConfig();
            return $proceed();
        }

        $fields = $groups[ConfigProvider::CODE]['groups']['sezzle_payment']['fields'];

        $new = [
            'public_key' => $this->isInherit('public_key', $fields)
                ? $old['public_key'] : (string)$fields['public_key']['value'],
            'private_key' => $this->isInherit('private_key', $fields)
                ? $old['private_key'] : (string)$fields['private_key']['value'],
            'payment_mode' => $this->isInherit('payment_mode', $fields)
                ? $old['payment_mode'] : (string)$fields['payment_mode']['value'],
        ];

        if ($old === $new) {
            $this->v2->sendConfig();
            return $proceed();
        }

        try {
            if ($this->authenticationService->validateAPIKeys(
                $new['public_key'],
                $new['private_key'],
                $new['payment_mode']
            )) {
                $this->v2->sendConfig();
                return $proceed();
            }
        } catch (ValidationException $e) {
            $this->helper->logSezzleActions($e->getMessage());
        }

        $this->messageManager->addErrorMessage(__('Unable to validate the Sezzle API Keys.'));

        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath(
            'adminhtml/system_config/edit',
            [
                '_current' => ['section', 'website', 'store'],
                '_nosid' => true
            ]
        );
    }

    /**
     * Get config path
     *
     * @param string $key
     * @return string
     */
    private function getPath(string $key): string
    {
        return sprintf('payment/%s/%s', ConfigProvider::CODE, $key);
    }

    /**
     * Checks if "inherit" is true
     *
     * @param string $key
     * @param array $fields
     * @return bool
     */
    private function isInherit(string $key, array $fields): bool
    {
        return isset($fields[$key]['inherit']) && $fields[$key]['inherit'];
    }
}
