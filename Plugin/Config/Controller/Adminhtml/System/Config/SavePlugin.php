<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Plugin\Config\Controller\Adminhtml\System\Config;

use Closure;
use Magento\Config\Model\Config;
use Magento\Framework\App\Config\Storage\WriterInterface;
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
use Sezzle\Sezzlepay\Gateway\Config\Config as SezzleConfig;

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
     * @var WriterInterface
     */
    private $configWriter;

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
     * @param WriterInterface $configWriter
     */
    public function __construct(
        ManagerInterface      $messageManager,
        RedirectFactory       $resultRedirectFactory,
        Data                  $helper,
        RequestInterface      $request,
        Config                $config,
        V2Interface           $v2,
        AuthenticationService $authenticationService,
        UrlInterface          $urlManager,
        WriterInterface       $configWriter
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
        $this->configWriter = $configWriter;
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
        $sezzleEnabled = isset($newConfig['sezzle_enabled']) && $newConfig['sezzle_enabled'];

        try {
            $merchantUUID = '';
            // only validate keys if they are changed
            if ($this->hasKeysChanged($oldConfig, $newConfig)) {
                if (!$merchantUUID = $this->authenticationService->validateAPIKeys(
                    $newConfig[SezzleConfig::KEY_PUBLIC_KEY],
                    $newConfig[SezzleConfig::KEY_PRIVATE_KEY],
                    $newConfig[SezzleConfig::KEY_PAYMENT_MODE]
                )) {
                    throw new ValidationException(__('Auth token not found.'));
                }
            }

            $goAhead = $proceed();

            if ($merchantUUID) {
                $this->configWriter->save(
                    sprintf('payment/%s/%s', ConfigProvider::CODE, SezzleConfig::KEY_MERCHANT_UUID),
                    $merchantUUID
                );
            }

            // sending config data to Sezzle
            try {
                unset(
                    $newConfig[SezzleConfig::KEY_PUBLIC_KEY],
                    $newConfig[SezzleConfig::KEY_PRIVATE_KEY],
                    $newConfig[SezzleConfig::KEY_PAYMENT_MODE]
                );
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
                SezzleConfig::KEY_PUBLIC_KEY => $oldConfig[SezzleConfig::KEY_PUBLIC_KEY],
                SezzleConfig::KEY_PRIVATE_KEY => $oldConfig[SezzleConfig::KEY_PRIVATE_KEY],
                SezzleConfig::KEY_PAYMENT_MODE => $oldConfig[SezzleConfig::KEY_PAYMENT_MODE]
            ] !== [
                SezzleConfig::KEY_PUBLIC_KEY => $newConfig[SezzleConfig::KEY_PUBLIC_KEY],
                SezzleConfig::KEY_PRIVATE_KEY => $newConfig[SezzleConfig::KEY_PRIVATE_KEY],
                SezzleConfig::KEY_PAYMENT_MODE => $newConfig[SezzleConfig::KEY_PAYMENT_MODE]
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
            SezzleConfig::KEY_ACTIVE =>
                $this->config->getConfigDataValue($this->getPath(SezzleConfig::KEY_ACTIVE)),
            SezzleConfig::KEY_PUBLIC_KEY =>
                $this->config->getConfigDataValue($this->getPath(SezzleConfig::KEY_PUBLIC_KEY)),
            SezzleConfig::KEY_PRIVATE_KEY =>
                $this->config->getConfigDataValue($this->getPath(SezzleConfig::KEY_PRIVATE_KEY)),
            SezzleConfig::KEY_PAYMENT_MODE =>
                $this->config->getConfigDataValue($this->getPath(SezzleConfig::KEY_PAYMENT_MODE)),
            SezzleConfig::KEY_MIN_CHECKOUT_AMOUNT =>
                $this->config->getConfigDataValue($this->getPath(SezzleConfig::KEY_MIN_CHECKOUT_AMOUNT)),
            SezzleConfig::KEY_WIDGET_PDP =>
                $this->config->getConfigDataValue($this->getPath(SezzleConfig::KEY_WIDGET_PDP)),
            SezzleConfig::KEY_WIDGET_CART =>
                $this->config->getConfigDataValue($this->getPath(SezzleConfig::KEY_WIDGET_CART)),
            SezzleConfig::KEY_WIDGET_INSTALLMENT =>
                $this->config->getConfigDataValue($this->getPath(SezzleConfig::KEY_WIDGET_INSTALLMENT)),
            SezzleConfig::KEY_INCONTEXT_ACTIVE =>
                $this->config->getConfigDataValue($this->getPath(SezzleConfig::KEY_INCONTEXT_ACTIVE)),
            SezzleConfig::KEY_INCONTEXT_MODE =>
                $this->config->getConfigDataValue($this->getPath(SezzleConfig::KEY_INCONTEXT_MODE)),
            SezzleConfig::KEY_PAYMENT_ACTION =>
                $this->config->getConfigDataValue($this->getPath(SezzleConfig::KEY_PAYMENT_ACTION)),
            SezzleConfig::KEY_TOKENIZE =>
                $this->config->getConfigDataValue($this->getPath(SezzleConfig::KEY_TOKENIZE)),
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

        $sezzleEnabled = !isset($paymentFields[SezzleConfig::KEY_ACTIVE]) ?
            $this->config->getConfigDataValue($this->getPath(SezzleConfig::KEY_ACTIVE)) :
            ($this->isInherit(SezzleConfig::KEY_ACTIVE, $paymentFields) ? $oldConfig[SezzleConfig::KEY_ACTIVE]
                : $paymentFields[SezzleConfig::KEY_ACTIVE]['value']);

        return [
            'sezzle_enabled' => (bool)$sezzleEnabled,
            SezzleConfig::KEY_PUBLIC_KEY => $this->isInherit(SezzleConfig::KEY_PUBLIC_KEY, $paymentFields)
                ? $oldConfig[SezzleConfig::KEY_PUBLIC_KEY] :
                (string)$paymentFields[SezzleConfig::KEY_PUBLIC_KEY]['value'],
            SezzleConfig::KEY_PRIVATE_KEY => $this->isInherit(SezzleConfig::KEY_PRIVATE_KEY, $paymentFields)
                ? $oldConfig[SezzleConfig::KEY_PRIVATE_KEY] :
                (string)$paymentFields[SezzleConfig::KEY_PRIVATE_KEY]['value'],
            SezzleConfig::KEY_PAYMENT_MODE => $this->isInherit(SezzleConfig::KEY_PAYMENT_MODE, $paymentFields)
                ? $oldConfig[SezzleConfig::KEY_PAYMENT_MODE] :
                (string)$paymentFields[SezzleConfig::KEY_PAYMENT_MODE]['value'],
            SezzleConfig::KEY_MIN_CHECKOUT_AMOUNT =>
                $this->isInherit(SezzleConfig::KEY_MIN_CHECKOUT_AMOUNT, $paymentFields)
                    ? $oldConfig[SezzleConfig::KEY_MIN_CHECKOUT_AMOUNT] :
                    (float)$paymentFields[SezzleConfig::KEY_MIN_CHECKOUT_AMOUNT]['value'],
            'pdp_widget_enabled' => $this->isInherit(SezzleConfig::KEY_WIDGET_PDP, $widgetFields)
                ? (bool)$oldConfig[SezzleConfig::KEY_WIDGET_PDP] : (bool)$widgetFields[SezzleConfig::KEY_WIDGET_PDP]['value'],
            'cart_widget_enabled' => $this->isInherit(SezzleConfig::KEY_WIDGET_CART, $widgetFields)
                ? (bool)$oldConfig[SezzleConfig::KEY_WIDGET_CART] :
                (bool)$widgetFields[SezzleConfig::KEY_WIDGET_CART]['value'],
            'installment_widget_enabled' => $this->isInherit(SezzleConfig::KEY_WIDGET_INSTALLMENT, $widgetFields)
                ? (bool)$oldConfig[SezzleConfig::KEY_WIDGET_INSTALLMENT] :
                (bool)$widgetFields[SezzleConfig::KEY_WIDGET_INSTALLMENT]['value'],
            'in_context_checkout_enabled' => $this->isInherit(SezzleConfig::KEY_INCONTEXT_ACTIVE, $inContextFields)
                ? (bool)$oldConfig[SezzleConfig::KEY_INCONTEXT_ACTIVE] :
                (bool)$inContextFields[SezzleConfig::KEY_INCONTEXT_ACTIVE]['value'],
            'in_context_checkout_mode' => $this->isInherit(SezzleConfig::KEY_INCONTEXT_MODE, $inContextFields)
                ? (bool)$oldConfig[SezzleConfig::KEY_INCONTEXT_MODE] :
                (isset($inContextFields[SezzleConfig::KEY_INCONTEXT_MODE]) ?
                    (string)$inContextFields[SezzleConfig::KEY_INCONTEXT_MODE]['value'] : ''),
            SezzleConfig::KEY_PAYMENT_ACTION => $this->isInherit(SezzleConfig::KEY_PAYMENT_ACTION, $paymentFields)
                ? $oldConfig[SezzleConfig::KEY_PAYMENT_ACTION] :
                (string)$paymentFields[SezzleConfig::KEY_PAYMENT_ACTION]['value'],
            'tokenization_enabled' => $this->isInherit(SezzleConfig::KEY_TOKENIZE, $paymentFields)
                ? (bool)$oldConfig[SezzleConfig::KEY_TOKENIZE] : (bool)$paymentFields[SezzleConfig::KEY_TOKENIZE]['value'],
            'store_url' => $this->urlManager->getBaseUrl()
        ];
    }
}
