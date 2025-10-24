<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Block\Adminhtml\System\Config\Form;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Backend\Block\Template\Context;

/**
 * Express Checkout Field - Only shows if public and private keys are set and feature flag is enabled
 */
class ExpressCheckoutField extends Field
{
    /**
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Render element HTML
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $publicKey = $this->_scopeConfig->getValue(
            'payment/sezzlepay/public_key',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        $privateKey = $this->_scopeConfig->getValue(
            'payment/sezzlepay/private_key',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        // Check if both keys are set
        if (empty($publicKey) || empty($privateKey)) {
            return '<tr id="row_' . $element->getHtmlId() . '">
                        <td class="label"><label for="' . $element->getHtmlId() . '"><span>' . $element->getLabel() . '</span></label></td>
                        <td class="value">
                            <div class="message message-notice">
                                <div>Express Checkout is only available after you configure your Public Key and Private Key.</div>
                            </div>
                        </td>
                        <td class=""></td>
                    </tr>';
        }

        // Check feature flag via API
        if (!$this->isExpressCheckoutEnabled($publicKey, $privateKey)) {
            return '<tr id="row_' . $element->getHtmlId() . '">
                        <td class="label"><label for="' . $element->getHtmlId() . '"><span>' . $element->getLabel() . '</span></label></td>
                        <td class="value">
                            <div class="message message-notice">
                                <div>Express Checkout is not available for your merchant account. Please contact Sezzle support for more information.</div>
                            </div>
                        </td>
                        <td class=""></td>
                    </tr>';
        }

        return parent::render($element);
    }

    /**
     * Check if express checkout is enabled via feature flag API
     *
     * @param string $publicKey
     * @param string $privateKey
     * @return bool
     */
    private function isExpressCheckoutEnabled(string $publicKey, string $privateKey): bool
    {
        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $sezzleConfig = $objectManager->get(\Sezzle\Sezzlepay\Gateway\Config\Config::class);
            $jsonSerializer = $objectManager->get(\Magento\Framework\Serialize\Serializer\Json::class);
            $helper = $objectManager->get(\Sezzle\Sezzlepay\Helper\Data::class);

            // Get auth token first
            $authToken = $this->getAuthToken($publicKey, $privateKey);
            if (!$authToken) {
                return false;
            }

            // Make request to feature flag endpoint
            $url = $sezzleConfig->getGatewayURL() . '/v2/feature-flags/is-express-checkout';

            $curl = new \Magento\Framework\HTTP\Client\Curl();
            $curl->setTimeout(80);
            $curl->setHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $authToken
            ]);

            $log = [
                'log_origin' => __METHOD__,
                'request' => [
                    'uri' => $url
                ]
            ];

            $curl->get($url);
            $responseJSON = $curl->getBody();
            $response = $jsonSerializer->unserialize($responseJSON);
            
            $log['response'] = [
                'status' => $curl->getStatus(),
                'body' => $response
            ];

            $helper->logSezzleActions($log);

            // Return true if response is true
            return isset($response) && $response === true;
        } catch (\Exception $e) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $helper = $objectManager->get(\Sezzle\Sezzlepay\Helper\Data::class);
            $helper->logSezzleActions([
                'log_origin' => __METHOD__,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get authentication token
     *
     * @param string $publicKey
     * @param string $privateKey
     * @return string|null
     */
    private function getAuthToken(string $publicKey, string $privateKey): ?string
    {
        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $sezzleConfig = $objectManager->get(\Sezzle\Sezzlepay\Gateway\Config\Config::class);
            $jsonSerializer = $objectManager->get(\Magento\Framework\Serialize\Serializer\Json::class);
            $helper = $objectManager->get(\Sezzle\Sezzlepay\Helper\Data::class);

            $data = [
                'public_key' => $publicKey,
                'private_key' => $privateKey
            ];

            $url = $sezzleConfig->getGatewayURL() . '/authentication';

            $curl = new \Magento\Framework\HTTP\Client\Curl();
            $curl->setTimeout(80);
            $curl->setHeaders([
                'Content-Type' => 'application/json'
            ]);

            $curl->post($url, $jsonSerializer->serialize($data));

            $responseJSON = $curl->getBody();
            $response = $jsonSerializer->unserialize($responseJSON);

            if (isset($response['token'])) {
                return $response['token'];
            }

            return null;
        } catch (\Exception $e) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $helper = $objectManager->get(\Sezzle\Sezzlepay\Helper\Data::class);
            $helper->logSezzleActions([
                'log_origin' => __METHOD__,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
