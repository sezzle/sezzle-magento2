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
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem\Driver\File;
use Sezzle\Sezzlepay\Logger\Logger;
use Sezzle\Sezzlepay\Gateway\Config\Config;
use Magento\Framework\Serialize\Serializer\Json;

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
     * @var Json
     */
    private $jsonSerializer;

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
     * @var Config
     */
    private $config;

    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param File $file
     * @param Json $jsonSerializer
     * @param Logger $logger
     * @param CustomerSession $customerSession
     * @param ProductMetadataInterface $productMetadata
     * @param Config $config
     */
    public function __construct(
        Context                  $context,
        File                     $file,
        Json                     $jsonSerializer,
        Logger                   $logger,
        CustomerSession          $customerSession,
        ProductMetadataInterface $productMetadata,
        Config                   $config
    )
    {
        $this->file = $file;
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
        $this->customerSession = $customerSession;
        $this->productMetadata = $productMetadata;
        $this->config = $config;
        parent::__construct($context);
    }

    /**
     * Dump Sezzle log actions
     *
     * @param string|array|null $data
     * @return void
     */
    public function logSezzleActions($data = null): void
    {
        try {
            if (!$this->config->isLogTrackerEnabled()) {
                return;
            }

            if (is_array($data)) {
                $data = $this->jsonSerializer->serialize($data);
            }

            $customerSessionId = $this->customerSession->getSessionId();
            $logData = $customerSessionId . ' ' . $data;
            $this->logger->info($logData);
        } catch (NoSuchEntityException|InputException $e) {
        }
    }

    /**
     * Export CSV string to array
     *
     * @param string $content
     * @return array
     */
    public function csvToArray(string $content): array
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
    public function snakeCaseToTitleCase(string $name): string
    {
        $name = str_replace("_", " ", $name);
        return ucwords($name);
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
                $contents = $this->jsonSerializer->unserialize($file);
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
    public function getAmountInCents(float $amount): int
    {
        return (int)(round(
            $amount * 100,
            self::PRECISION
        ));
    }

    /**
     * Get platform details
     *
     * @param bool $encode
     * @return string
     */
    public function getPlatformDetails(bool $encode = false): string
    {
        try {
            $platformDetails = [
                'id' => 'Magento',
                'version' => $this->productMetadata->getEdition() . ' ' . $this->productMetadata->getVersion(),
                'plugin_version' => $this->getVersion()
            ];
            $jsonData = $this->jsonSerializer->serialize($platformDetails);
            if (!$encode) {
                return $jsonData;
            }
            return base64_encode($jsonData);
        } catch (Exception $e) {
            $this->logSezzleActions('Error getting platform details: ' . $e->getMessage());
        }
        return '';
    }
}
