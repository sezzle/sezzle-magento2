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
     * Validate API Key and send configuration data to Sezzle
     *
     * @param Save $subject
     * @param Closure $proceed
     * @return Redirect
     */
    public function aroundExecute(Save $subject, Closure $proceed): Redirect
    {
        $groups = $this->request->getPost('groups');

        $oldConfig = $this->getOldConfig();

        $isSezzleConfig = isset($groups[ConfigProvider::CODE]) &&
            isset($groups[ConfigProvider::CODE]['groups']) &&
            isset($groups[ConfigProvider::CODE]['groups']['sezzle_payment']) &&
            isset($groups[ConfigProvider::CODE]['groups']['sezzle_payment']['fields']);
        if (!$isSezzleConfig) {
            return $proceed();
        }

        $fields = $groups[ConfigProvider::CODE]['groups']['sezzle_payment']['fields'];

        $newConfig = $this->getNewConfig($oldConfig, $fields);

        if ($oldConfig === $newConfig) {
            return $proceed();
        }

        try {
            if ($this->authenticationService->validateAPIKeys(
                $newConfig['public_key'],
                $newConfig['private_key'],
                $newConfig['payment_mode']
            )) {
                $goAhead = $proceed();
                try {
                    $this->v2->sendConfig($this->getNewConfig($oldConfig, $fields));
                } catch (LocalizedException $e) {
                    $this->helper->logSezzleActions($e->getMessage());
                }
                return $goAhead;

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

    /**
     * Gets the old config data
     *
     * @return array
     */
    private function getOldConfig(): array
    {
        return [
            'active' => $this->config->getConfigDataValue($this->getPath('active')),
            'merchant_uuid' => $this->config->getConfigDataValue($this->getPath('merchant_uuid')),
            'public_key' => $this->config->getConfigDataValue($this->getPath('public_key')),
            'private_key' => $this->config->getConfigDataValue($this->getPath('private_key')),
            'payment_mode' => $this->config->getConfigDataValue($this->getPath('payment_mode')),
            'widget_pdp' => $this->config->getConfigDataValue($this->getPath('widget_pdp')),
            'widget_cart' => $this->config->getConfigDataValue($this->getPath('widget_cart')),
            'widget_installment' => $this->config->getConfigDataValue($this->getPath('widget_installment')),
            'active_in_context' => $this->config->getConfigDataValue($this->getPath('active_in_context')),
            'in_context_mode' => $this->config->getConfigDataValue($this->getPath('in_context_mode')),
            'payment_action' => $this->config->getConfigDataValue($this->getPath('payment_action')),
            'tokenize' => $this->config->getConfigDataValue($this->getPath('tokenize')),
        ];
    }

    /**
     * Gets the new config data
     *
     * @param array $oldConfig
     * @param array $fields
     * @return array
     */
    private function getNewConfig(array $oldConfig, array $fields): array
    {
        return [
            'sezzle_enabled' => $this->isInherit('active', $fields)
                ? $oldConfig['sezzle_enabled'] : (bool)$fields['active']['value'],
            'merchant_uuid' => $this->isInherit('merchant_uuid', $fields)
                ? $oldConfig['merchant_uuid'] : (string)$fields['merchant_uuid']['value'],
            'pdp_widget_enabled' => $this->isInherit('widget_pdp', $fields)
                ? $oldConfig['pdp_widget_enabled'] : (bool)$fields['widget_pdp']['value'],
            'cart_widget_enabled' => $this->isInherit('widget_cart', $fields)
                ? $oldConfig['cart_widget_enabled'] : (bool)$fields['widget_cart']['value'],
            'installment_widget_enabled' => $this->isInherit('widget_installment', $fields)
                ? $oldConfig['installment_widget_enabled'] : (bool)$fields['widget_installment']['value'],
            'in_context_checkout_enabled' => $this->isInherit('active_in_context', $fields)
                ? $oldConfig['in_context_checkout_enabled'] : (bool)$fields['active_in_context']['value'],
            'in_context_checkout_mode' => $this->isInherit('in_context_mode', $fields)
                ? $oldConfig['in_context_checkout_mode'] : (string)$fields['in_context_mode']['value'],
            'payment_action' => $this->isInherit('payment_action', $fields)
                ? $oldConfig['payment_action'] : (string)$fields['payment_action']['value'],
            'tokenization_enabled' => $this->isInherit('tokenize', $fields)
                ? $oldConfig['tokenization_enabled'] : (bool)$fields['tokenize']['value']
        ];
    }
}
