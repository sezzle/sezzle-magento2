<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Block\Adminhtml\System\Config\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Sezzle\Sezzlepay\Model\System\Config\Container\SezzleConfigInterface;

/**
 * Class WidgetQueue
 * @package Sezzle\Sezzlepay\Block\Adminhtml\System\Config
 */
class WidgetQueue extends Field
{
    const SEZZLE_WIDGET_QUEUE_ROUTE = "sezzle/widget/queue";
    const WIDGET_QUEUE_SLA = " +7 days";

    protected $_template = 'Sezzle_Sezzlepay::system/config/widget_queue.phtml';

    /**
     * @var SezzleConfigInterface
     */
    private $sezzleConfig;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * WidgetQueue constructor.
     * @param Context $context
     * @param SezzleConfigInterface $sezzleConfig
     * @param DateTime $dateTime
     * @param array $data
     */
    public function __construct(
        Context $context,
        SezzleConfigInterface $sezzleConfig,
        DateTime $dateTime,
        array $data = []
    ) {
        $this->sezzleConfig = $sezzleConfig;
        $this->dateTime = $dateTime;
        parent::__construct($context, $data);
    }

    /**
     * Render Element
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Get Element HTML
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $fieldCommentHtml = "<div>Widgets not visible after enabling? Submit a request to be added to the Sezzle widget configuration queue. Our team will configure widgets on your behalf..</div>";
        $checkIssueStatusHtml = "<div id=\"check_issue_status\"><a id=\"enable_widget_request\">Click here</a> if widgets are now visible on your site.</div>";
        $html = $this->_toHtml() . "<br>";
        if (!$this->canAddToWidgetQueue()) {
            $html .= "<br>" . $checkIssueStatusHtml;
        }

        $html .= "<br>" . $fieldCommentHtml;
        return $html;
    }

    /**
     * Get Widget Queue URL
     *
     * @param bool $isResolved
     * @return string
     */
    public function getWidgetQueueUrl($isResolved = false)
    {
        $isResolvedParam = $isResolved ? '?isResolved=true' : '';
        $route = self::SEZZLE_WIDGET_QUEUE_ROUTE . $isResolvedParam;
        return $this->getUrl($route);
    }

    /**
     * Check if merchant can request to add to widget queue
     *
     * @return bool
     */
    public function canAddToWidgetQueue()
    {
        if (!$this->sezzleConfig->getWidgetTicketCreatedAt()) {
            return true;
        }
        $currentTimestamp = $this->dateTime->timestamp('now');
        $widgetTicketCreatedAtTimestamp = $this->dateTime->timestamp($this->sezzleConfig->getWidgetTicketCreatedAt() . self::WIDGET_QUEUE_SLA);
        return $currentTimestamp > $widgetTicketCreatedAtTimestamp;
    }

    /**
     * Get Button Html
     *
     * @return mixed
     * @throws LocalizedException
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setData(
            [
                'id' => 'widget_queue',
                'label' => __('Request'),
            ]
        );

        return $button->toHtml();
    }
}
