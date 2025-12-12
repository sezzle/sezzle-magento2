<?php
/**
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Controller\Adminhtml\Download;

use Exception;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Controller\Adminhtml\System;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NotFoundException;
use Sezzle\Sezzlepay\Helper\Data;

/**
 * Class SezzleLog
 * Enables custom client log file to be accessed via an admin link
 */
class SezzleLog extends System
{
    /**
     * @var FileFactory
     */
    private $fileFactory;

    /**
     * @var Data
     */
    private $helper;

    /**
     * ClientLog constructor.
     * @param Context $context
     * @param FileFactory $fileFactory
     * @param Data $helper
     */
    public function __construct(
        Context     $context,
        FileFactory $fileFactory,
        Data        $helper
    )
    {
        $this->fileFactory = $fileFactory;
        $this->helper = $helper;

        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|ResultInterface
     * @throws NotFoundException
     */
    public function execute()
    {
        $filePath = $this->getFilePath();

        $fileName = basename($filePath);

        try {
            return $this->fileFactory->create(
                $fileName,
                [
                    'type' => 'filename',
                    'value' => $filePath
                ]
            );
        } catch (Exception $e) {
            throw new NotFoundException(__($e->getMessage()));
        }
    }

    /**
     * Get the path to the current day's log file
     *
     * @return string
     */
    private function getFilePath(): string
    {
        $datedLogPath = $this->helper->getCurrentLogFilePath();

        // Use the dated log file if it exists (current day's logs)
        // Fall back to base file for backwards compatibility
        if (file_exists(BP . $datedLogPath)) {
            return $datedLogPath;
        }

        return Data::SEZZLE_LOG_FILE_PATH;
    }
}
