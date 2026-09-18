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

            // Redact before serializing, not at the call sites. This log file is routinely
            // sent to Sezzle support by merchants, and a credential that reaches it has
            // left the merchant's control.
            if (is_array($data)) {
                $data = $this->jsonSerializer->serialize(Util::redactSensitive($data));
            } elseif (is_string($data)) {
                $data = Util::redactSensitiveText($data);
            }

            $customerSessionId = $this->customerSession->getSessionId();
            $logData = $customerSessionId . ' ' . $data;
            $this->logger->info($logData);
        } catch (NoSuchEntityException|InputException $e) {
        } catch (\Throwable $e) {
            // Broadened deliberately. Callers on the order-failure path log ahead of releasing
            // the shopper's authorization, and several do so from inside a catch block, so an
            // exception escaping here costs the release or puts the shopper back on a raw error
            // page. Serializing a message with invalid UTF-8 and a failing log handler both
            // reach this. Reported through Magento's own logger so it does not go unnoticed.
            $this->logCriticalFailure('Could not write to the Sezzle log', $e);
        }
    }

    /**
     * Record a failure in Magento's own log, whatever the Sezzle log tracker is set to.
     *
     * logSezzleActions() returns early when payment/sezzlepay/log_tracker is off, and that
     * setting is store-scoped. That is the right behaviour for the running commentary this
     * module writes, but it is the wrong behaviour for a checkout that failed after the
     * shopper was authorized: the order-placement entry points catch \Throwable and do not
     * rethrow, so with the tracker off there would be no error report, nothing in system.log
     * or exception.log, and nothing for an APM to see - a silent failed checkout.
     *
     * This writes to the PSR logger the helper context already carries, so the record lands
     * in Magento's own log alongside whatever the Sezzle log does or does not capture.
     *
     * Never throws: callers use it on the failure path, ahead of releasing the shopper's
     * authorization.
     *
     * @param string $message
     * @param \Throwable|null $e
     * @return void
     */
    public function logCriticalFailure(string $message, ?\Throwable $e = null): void
    {
        try {
            $this->_logger->critical($message, $e ? ['exception' => $e] : []);
        } catch (\Throwable $loggingFailure) {
            // Nothing further to try - the logger itself is the thing that failed, and the
            // authorization release this runs ahead of matters more than this line.
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

        $lines = str_getcsv($content, "\n", "\"", "\\");
        foreach ($lines as $index => $line) {
            // Skip empty lines
            if (trim($line) === '') {
                continue;
            }

            if ($index == 0 || $index == 2) {
                if ($index == 2) {
                    $summary = $data;
                    unset($data);
                    $data = ['header' => [], 'data' => []];
                }
                $data['header'] = str_getcsv($line, ",", "\"", "\\");
            } else {
                $values = str_getcsv($line, ",", "\"", "\\");

                // Validate that header and values have the same number of elements
                if (count($data['header']) !== count($values)) {
                    $this->logSezzleActions(sprintf(
                        'CSV parsing warning: Header has %d columns but row %d has %d columns. Skipping row. Header: %s, Values: %s',
                        count($data['header']),
                        $index,
                        count($values),
                        json_encode($data['header']),
                        json_encode($values)
                    ));
                    continue;
                }

                $row = array_combine($data['header'], $values);
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
     * Get the path to the current day's log file
     * RotatingFileHandler creates dated log files: sezzlepay-YYYY-MM-DD.log
     *
     * @param string|null $date Date in Y-m-d format, defaults to today
     * @return string
     */
    public function getCurrentLogFilePath(?string $date = null): string
    {
        $dateStr = $date ?: date('Y-m-d');
        return str_replace('.log', '-' . $dateStr . '.log', self::SEZZLE_LOG_FILE_PATH);
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
