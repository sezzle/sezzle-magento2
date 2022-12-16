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
use Magento\Framework\UrlInterface;

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
     * @var UrlInterface
     */
    private $urlManager;

    /**
     * SavePlugin constructor.
     * @param ManagerInterface $messageManager
     * @param RedirectFactory $resultRedirectFactory
     * @param Data $helper
     * @param RequestInterface $request
     * @param Config $config
     * @param V2Interface $v2
     * @param AuthenticationService $authenticationService
     * @param UrlInterface $urlManager
     */
    public function __construct(
        ManagerInterface      $messageManager,
        RedirectFactory       $resultRedirectFactory,
        Data                  $helper,
        RequestInterface      $request,
        Config                $config,
        V2Interface           $v2,
        AuthenticationService $authenticationService,
        UrlInterface          $urlManager
    )
    {
        $this->messageManager = $messageManager;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->helper = $helper;
        $this->request = $request;
        $this->config = $config;
        $this->v2 = $v2;
        $this->authenticationService = $authenticationService;
        $this->urlManager = $urlManager;
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

        // don't do anything if the config data are not of Sezzle
        $isSezzleConfig = isset($groups[ConfigProvider::CODE]['groups']['sezzle_payment']['fields']);
        if (!$isSezzleConfig) {
            return $proceed();
        }

        // checking if the config data has been altered or not
        $oldConfig = $this->getOldConfig();
        $newConfig = $this->getNewConfig($oldConfig, $groups[ConfigProvider::CODE]['groups']);
        if ($oldConfig === $newConfig) {
            return $proceed();
        }

        try {
            // only validate keys if they are changed
            if ($this->hasKeysChanged($oldConfig, $newConfig)) {
                if (!$this->authenticationService->validateAPIKeys(
                    $newConfig['public_key'],
                    $newConfig['private_key'],
                    $newConfig['payment_mode']
                )) {
                    throw new ValidationException(__('Auth token not found.'));
                }
            }

            $goAhead = $proceed();

            // sending config data to Sezzle
            try {
                unset($newConfig['public_key'], $newConfig['private_key'], $newConfig['payment_mode']);
                $this->v2->sendConfig($newConfig);
            } catch (LocalizedException $e) {
                $this->helper->logSezzleActions($e->getMessage());
            }
            return $goAhead;
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
     * Checking if the keys values has been changed or not
     *
     * @param array $oldConfig
     * @param array $newConfig
     * @return bool
     */
    private function hasKeysChanged(array $oldConfig, array $newConfig): bool
    {
        return [
                'public_key' => $oldConfig['public_key'],
                'private_key' => $oldConfig['private_key'],
                'payment_mode' => $oldConfig['payment_mode']
            ] !== [
                'public_key' => $newConfig['public_key'],
                'private_key' => $newConfig['private_key'],
                'payment_mode' => $newConfig['payment_mode']
            ];
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
            'min_checkout_amount' => $this->config->getConfigDataValue($this->getPath('min_checkout_amount')),
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
     * @param array $configGroups
     * @return array
     */
    private function getNewConfig(array $oldConfig, array $configGroups): array
    {
        $paymentFields = $configGroups['sezzle_payment']['fields'];
        $widgetFields = $configGroups['sezzle_widget']['fields'];
        $inContextFields = $configGroups['sezzle_payment_in_context']['fields'];

        return [
            'sezzle_enabled' => $this->isInherit('active', $paymentFields)
                ? $oldConfig['sezzle_enabled'] : (bool)$paymentFields['active']['value'],
            'public_key' => $this->isInherit('public_key', $paymentFields)
                ? $oldConfig['public_key'] : (string)$paymentFields['public_key']['value'],
            'private_key' => $this->isInherit('private_key', $paymentFields)
                ? $oldConfig['private_key'] : (string)$paymentFields['private_key']['value'],
            'payment_mode' => $this->isInherit('payment_mode', $paymentFields)
                ? $oldConfig['payment_mode'] : (string)$paymentFields['payment_mode']['value'],
            'min_checkout_amount' => $this->isInherit('min_checkout_amount', $paymentFields)
                ? $oldConfig['min_checkout_amount'] : (float)$paymentFields['min_checkout_amount']['value'],
            'pdp_widget_enabled' => $this->isInherit('widget_pdp', $widgetFields)
                ? $oldConfig['pdp_widget_enabled'] : (bool)$widgetFields['widget_pdp']['value'],
            'cart_widget_enabled' => $this->isInherit('widget_cart', $widgetFields)
                ? $oldConfig['cart_widget_enabled'] : (bool)$widgetFields['widget_cart']['value'],
            'installment_widget_enabled' => $this->isInherit('widget_installment', $widgetFields)
                ? $oldConfig['installment_widget_enabled'] : (bool)$widgetFields['widget_installment']['value'],
            'in_context_checkout_enabled' => $this->isInherit('active_in_context', $inContextFields)
                ? $oldConfig['in_context_checkout_enabled'] : (bool)$inContextFields['active_in_context']['value'],
            'in_context_checkout_mode' => $this->isInherit('in_context_mode', $inContextFields)
                ? $oldConfig['in_context_checkout_mode'] :
                (isset($inContextFields['in_context_mode']) ?
                    (string)$inContextFields['in_context_mode']['value'] : ''),
            'payment_action' => $this->isInherit('payment_action', $paymentFields)
                ? $oldConfig['payment_action'] : (string)$paymentFields['payment_action']['value'],
            'tokenization_enabled' => $this->isInherit('tokenize', $paymentFields)
                ? $oldConfig['tokenization_enabled'] : (bool)$paymentFields['tokenize']['value'],
            'store_url' => $this->urlManager->getBaseUrl()
        ];
    }
}
