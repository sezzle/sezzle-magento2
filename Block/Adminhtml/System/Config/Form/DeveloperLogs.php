<?php
/**
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Block\Adminhtml\System\Config\Form;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Model\UrlInterface;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Phrase;
use Sezzle\Sezzlepay\Helper\Data;

/**
 * Displays links to available custom logs
 */
class DeveloperLogs extends Field
{
    const DOWNLOAD_PATH = 'sezzle/download';

    protected $logs = [
        'sezzleLog' => [
            'name' => 'Sezzle Log',
            'path' => Data::SEZZLE_LOG_FILE_PATH
        ]
    ];

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * DeveloperLogs constructor.
     * @param Context $context
     * @param DirectoryList $directoryList
     * @param UrlInterface $urlBuilder
     * @param array $data
     */
    public function __construct(
        Context $context,
        DirectoryList $directoryList,
        UrlInterface $urlBuilder,
        $data = []
    ) {
        $this->directoryList = $directoryList;
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $data);
    }

    /**
     * @inheritdoc
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate('system/config/logs.phtml');
        }
        return $this;
    }

    /**
     * Render log list
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        // Remove scope label
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Renders string as an html element
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * Returns markup for developer log field.
     *
     * @return Phrase|string
     */
    public function getLinks()
    {
        $links = $this->getLogFiles();

        if ($links) {
            $output = '';

            foreach ($links as $link) {
                $output .= '<a href="' . $link['link'] . '">' . $link['name'] . '</a><br />';
            }

            return $output;
        }
        return __('No logs are currently available.');
    }

    /**
     * Get list of available log files.
     *
     * @return array
     */
    private function getLogFiles()
    {
        $links = [];

        $path = $this->directoryList->getPath(DirectoryList::ROOT);

        foreach ($this->logs as $name => $data) {
            $filePath = $data['path'];

            $exists = file_exists($path . $filePath);

            if ($exists) {
                $links[] = ['link' => $this->urlBuilder->getUrl(self::DOWNLOAD_PATH . '/' . $name), 'name' => $data['name']];
            }
        }

        return $links;
    }
}
