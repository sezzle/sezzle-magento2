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
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Sezzle\Sezzlepay\Gateway\Config\Config;

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
     * @var Config
     */
    private $config;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * WidgetQueue constructor.
     * @param Context $context
     * @param Config $config
     * @param DateTime $dateTime
     * @param array $data
     */
    public function __construct(
        Context  $context,
        Config   $config,
        DateTime $dateTime,
        array    $data = []
    )
    {
        $this->config = $config;
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
        $t = parent::render($element);
        $output = '<div class="deprecated-message">';
        $output .= '<div class="comment">';
        $output .= __("Submit a request to get help on your widget configuration. Our team will work on quickly resolving the issue.");
        $output .= "</div></div>";
        return $output . $t;
    }

    /**
     * Get Element HTML
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * Get Widget Queue URL
     *
     * @return string
     */
    public function getWidgetQueueUrl()
    {
        return $this->getUrl(self::SEZZLE_WIDGET_QUEUE_ROUTE);
    }

    /**
     * Check if merchant can request to add to widget queue
     *
     * @return bool
     */
    public function canAddToWidgetQueue()
    {
        try {
            if (!$widgetTicketCreatedAt = $this->config->getWidgetTicketCreatedAt()) {
                return true;
            }
        } catch (InputException|NoSuchEntityException $e) {
            return true;
        }

        $currentTimestamp = $this->dateTime->timestamp('now');
        $widgetTicketCreatedAtTimestamp = $this->dateTime->timestamp($widgetTicketCreatedAt . self::WIDGET_QUEUE_SLA);
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
