<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Block\Adminhtml\System\Config\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Store\Model\ScopeInterface;
use Sezzle\Sezzlepay\Gateway\Config\Config;
use Sezzle\Sezzlepay\Model\Ui\ConfigProvider;
use Sezzle\Sezzlepay\Model\SezzleAds\Enrollment;

/**
 * Renders a Sezzle Ads setting only once Sezzle has enrolled the merchant in Sezzle Ads.
 *
 * A field that is not rendered is not posted, so hiding a setting leaves its saved value as it was.
 */
class SezzleAds extends Field
{
    /**
     * @var Enrollment
     */
    private $enrollment;

    /**
     * Enrollment per merchant UUID, for the fields rendered in this request.
     *
     * @var array<string, bool>
     */
    private $enrolled = [];

    /**
     * @param Context $context
     * @param Enrollment $enrollment
     * @param array $data
     */
    public function __construct(
        Context    $context,
        Enrollment $enrollment,
        array      $data = []
    )
    {
        $this->enrollment = $enrollment;
        parent::__construct($context, $data);
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        if (!$this->isEnrolled()) {
            return '';
        }

        return parent::render($element);
    }

    /**
     * Whether the merchant configured at the scope being edited is enrolled.
     *
     * @return bool
     */
    private function isEnrolled(): bool
    {
        $merchantUUID = trim((string)$this->getMerchantUUID());
        if (!array_key_exists($merchantUUID, $this->enrolled)) {
            $this->enrolled[$merchantUUID] = $this->enrollment->isEnrolled($merchantUUID);
        }

        return $this->enrolled[$merchantUUID];
    }

    /**
     * The merchant UUID at the scope the admin is editing, inherited from the scopes above it.
     *
     * @return string|null
     */
    private function getMerchantUUID(): ?string
    {
        $path = sprintf('payment/%s/%s', ConfigProvider::CODE, Config::KEY_MERCHANT_UUID);
        $request = $this->getRequest();

        $storeId = $request->getParam('store');
        if ($storeId) {
            return $this->_scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
        }

        $websiteId = $request->getParam('website');
        if ($websiteId) {
            return $this->_scopeConfig->getValue($path, ScopeInterface::SCOPE_WEBSITE, $websiteId);
        }

        return $this->_scopeConfig->getValue($path, ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
    }
}
