<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Helper;

use Exception;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Sezzle\Sezzlepay\Logger\Logger;
use Sezzle\Sezzlepay\Model\System\Config\Container\SezzleIdentity;
use Zend_Http_UserAgent_Mobile;
use Magento\Framework\Json\Helper\Data as JsonHelper;

/**
 * Sezzle Helper
 */
class Data extends AbstractHelper
{
    const PRECISION = 2;
    const SEZZLE_LOG_FILE_PATH = '/var/log/sezzlepay.log';
    const SEZZLE_MANUAL_INSTALL_COMPOSER_FILE_PATH = '/app/code/Sezzle/Sezzlepay/composer.json';
    const SEZZLE_COMPOSER_INSTALL_COMPOSER_FILE_PATH = '/vendor/sezzle/sezzlepay/composer.json';

    /**
     * @var File
     */
    private $file;
    /**
     * @var JsonHelper
     */
    private $jsonHelper;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var CustomerSession
     */
    private $customerSession;
    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param File $file
     * @param JsonHelper $jsonHelper
     * @param StoreManagerInterface $storeManager
     * @param Logger $logger
     * @param CustomerSession $customerSession
     * @param ProductMetadataInterface $productMetadata
     */
    public function __construct(
        Context $context,
        File $file,
        JsonHelper $jsonHelper,
        StoreManagerInterface $storeManager,
        Logger $logger,
        CustomerSession $customerSession,
        ProductMetadataInterface $productMetadata
    ) {
        $this->file = $file;
        $this->jsonHelper = $jsonHelper;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->customerSession = $customerSession;
        $this->productMetadata = $productMetadata;
        parent::__construct($context);
    }

    /**
     * Dump Sezzle log actions
     *
     * @param string|array|null $data
     * @return void
     */
    public function logSezzleActions($data = null)
    {
        try {
            $logTrackerEnabled = $this->scopeConfig->getValue(
                SezzleIdentity::XML_PATH_LOG_TRACKER,
                ScopeInterface::SCOPE_STORE,
                $this->storeManager->getStore()->getId()
            );
            if (!$logTrackerEnabled) {
                return;
            }

            if (is_array($data)) {
                $data = $this->jsonHelper->jsonEncode($data);
            }

            $customerSessionId = $this->customerSession->getSessionId();
            $logData = $customerSessionId . " " . $data;
            $this->logger->info($logData);
        } catch (NoSuchEntityException $e) {
        }
    }

    /**
     * Check if Device is Mobile or Tablet
     *
     * @return bool
     */
    public function isMobileOrTablet()
    {
        $userAgent = $this->_httpHeader->getHttpUserAgent();
        return Zend_Http_UserAgent_Mobile::match($userAgent, $_SERVER);
    }

    /**
     * Export CSV string to array
     *
     * @param string $content
     * @return array
     */
    public function csvToArray($content)
    {
        $data = ['header' => [], 'data' => []];
        $summary = [];
        $result = [];

        $lines = str_getcsv($content, "\n");
        foreach ($lines as $index => $line) {
            if ($index == 0 || $index == 2) {
                if ($index == 2) {
                    $summary = $data;
                    unset($data);
                }
                $data['header'] = str_getcsv($line);
            } else {
                $row = array_combine($data['header'], str_getcsv($line));
                $data['data'][] = $row;
            }
        }
        array_push($result, $summary, $data);
        return $result;
    }

    /**
     * Convert string from snake case to title case
     * @param string $name
     * @return string
     */
    public function snakeCaseToTitleCase($name)
    {
        $name = str_replace("_", " ", $name);
        $name = ucwords($name);
        return $name;
    }

    /**
     * Get Sezzle Module Version
     */
    public function getVersion()
    {
        try {
            if ($this->file->isExists(BP . self::SEZZLE_MANUAL_INSTALL_COMPOSER_FILE_PATH)) {
                $composerFilePath = BP . self::SEZZLE_MANUAL_INSTALL_COMPOSER_FILE_PATH;
            } else {
                $composerFilePath = BP . self::SEZZLE_COMPOSER_INSTALL_COMPOSER_FILE_PATH;
            }
            $file = $this->file->fileGetContents($composerFilePath);
            if ($file) {
                $contents = $this->jsonHelper->jsonDecode($file);
                if (is_array($contents) && isset($contents['version'])) {
                    return $contents['version'];
                }
            }
        } catch (FileSystemException $e) {
            $this->logSezzleActions("Module not found");
            return '--';
        }
        return '--';
    }

    /**
     * Get amount in cents
     *
     * @param float $amount
     * @return int
     */
    public function getAmountInCents($amount)
    {
        return (int)(round(
            $amount * 100,
            self::PRECISION
        ));
    }

    /**
     * Get encoded platform details
     *
     * @return string
     */
    public function getEncodedPlatformDetails()
    {
        try {
            $encodedDetails = "";
            $platformDetails = [
                "id" => "Magento",
                "version" => $this->productMetadata->getEdition() . " " . $this->productMetadata->getVersion(),
                "plugin_version" => $this->getVersion()
            ];
            $encodedDetails = base64_encode($this->jsonHelper->jsonEncode($platformDetails));
        } catch (Exception $e) {
            $this->logSezzleActions("Error getting platform details: " . $e->getMessage());
        }
        return $encodedDetails;
    }
}
