<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */
namespace Sezzle\Sezzlepay\Block\Adminhtml\SettlementReports\PayoutDetails;

use Magento\Backend\Block\Widget\Form\Container;

/**
 * Class View
 * @package Sezzle\Sezzlepay\Block\Adminhtml\SettlementReports\PayoutDetails
 */
class View extends Container
{
    /**
     * @var string
     */
    protected $_blockGroup = 'Sezzle_Sezzlepay';

    /**
     * Constructor
     *
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _construct()
    {
        $this->_objectId = 'payout_uuid';
        $this->_controller = 'adminhtml_settlementReports_payoutDetails';
        $this->_mode = 'view';

        parent::_construct();

        $this->buttonList->remove('save');
        $this->buttonList->remove('reset');
        $this->buttonList->remove('delete');

        $this->addButton(
            'download_payout',
            [
                'label' => __('Download'),
                'class' => __('download primary'),
                'onclick' => 'setLocation(\'' . $this->getDownloadUrl() . '\')',
                'id' => 'payout-view-download-button'
            ]
        );
    }

    /**
     * Get reports download URL
     *
     * @return string
     */
    public function getDownloadUrl()
    {
        return $this->getUrl(
            'sezzle/*/download',
            [ 'payout_uuid' => $this->getRequest()->getParam('payout_uuid')]
        );
    }
}
