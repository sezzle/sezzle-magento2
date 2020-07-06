<?php

namespace Sezzle\Sezzlepay\Block\Adminhtml\SettlementReports\PayoutDetails;

use Sezzle\Sezzlepay\Helper\Data;

class View extends \Magento\Backend\Block\Widget\Form\Container
{
    protected $_blockGroup = 'Sezzle_Sezzlepay';

    /**
     * Admin session
     *
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $_session;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * Backend session
     *
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $_backendSession;
    /**
     * @var Data
     */
    private $sezzleHelper;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Backend\Model\Auth\Session $backendSession
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Backend\Model\Auth\Session $backendSession,
        \Magento\Framework\Registry $registry,
        Data $sezzleHelper,
        array $data = []
    ) {
        $this->_backendSession = $backendSession;
        $this->sezzleHelper = $sezzleHelper;
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

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

    public function getDownloadUrl()
    {
        return $this->getUrl(
            'sezzle/*/download',
            [ 'payout_uuid' => $this->getRequest()->getParam('payout_uuid')]
        );
    }
}
