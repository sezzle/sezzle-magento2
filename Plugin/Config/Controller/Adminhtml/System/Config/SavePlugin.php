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
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Config\Controller\Adminhtml\System\Config\Save;
use Sezzle\Sezzlepay\Gateway\Http\AuthenticationService;
use Sezzle\Sezzlepay\Helper\Data;
use Sezzle\Sezzlepay\Model\Ui\ConfigProvider;

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
     * SavePlugin constructor.
     * @param ManagerInterface $messageManager
     * @param RedirectFactory $resultRedirectFactory
     * @param Data $helper
     * @param RequestInterface $request
     * @param Config $config
     * @param AuthenticationService $authenticationService
     */
    public function __construct(
        ManagerInterface      $messageManager,
        RedirectFactory       $resultRedirectFactory,
        Data                  $helper,
        RequestInterface      $request,
        Config                $config,
        AuthenticationService $authenticationService
    )
    {
        $this->messageManager = $messageManager;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->helper = $helper;
        $this->request = $request;
        $this->config = $config;
        $this->authenticationService = $authenticationService;
    }

    /**
     * Validate API Keys
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

        $fields = $groups[ConfigProvider::CODE]['groups']['sezzle_payment']['fields'];
        if (!isset($fields)) {
            return $proceed();
        }

        $new = [
            'public_key' => $this->isInherit('public_key', $fields)
                ? $old['public_key'] : (string)$fields['public_key']['value'],
            'private_key' => $this->isInherit('private_key', $fields)
                ? $old['private_key'] : (string)$fields['private_key']['value'],
            'payment_mode' => $this->isInherit('payment_mode', $fields)
                ? $old['payment_mode'] : (string)$fields['payment_mode']['value'],
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
            $this->helper->logSezzleActions($e->getMessage());
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
