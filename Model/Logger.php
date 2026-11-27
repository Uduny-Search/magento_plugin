<?php
/**
 * Uduny Logger
 *
 * Custom logger that writes to var/log/uduny/ directory.
 * Only logs when debug mode is enabled in configuration.
 */

declare(strict_types=1);

namespace UnusualDope\Uduny\Model;

use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File as FileDriver;

class Logger
{
    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var DirectoryList
     */
    private DirectoryList $directoryList;

    /**
     * @var FileDriver
     */
    private FileDriver $fileDriver;

    /**
     * Constructor
     *
     * @param Config $config
     * @param DirectoryList $directoryList
     * @param FileDriver $fileDriver
     */
    public function __construct(
        Config $config,
        DirectoryList $directoryList,
        FileDriver $fileDriver
    ) {
        $this->config = $config;
        $this->directoryList = $directoryList;
        $this->fileDriver = $fileDriver;
    }

    /**
     * Log debug message (only if debug mode enabled)
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function debug(string $message, array $context = []): void
    {
        if ($this->config->isDebugMode()) {
            $this->log('DEBUG', $message, $context);
        }
    }

    /**
     * Log info message (always logs)
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function info(string $message, array $context = []): void
    {
        $this->log('INFO', $message, $context);
    }

    /**
     * Log error message (always logs)
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function error(string $message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }

    /**
     * Write log entry to file
     *
     * @param string $level
     * @param string $message
     * @param array $context
     * @return void
     */
    private function log(string $level, string $message, array $context = []): void
    {
        try {
            $logDir = $this->getLogDirectory();
            $logFile = $logDir . '/' . date('Y-m-d') . '.log';

            // Create directory if it doesn't exist
            if (!$this->fileDriver->isDirectory($logDir)) {
                $this->fileDriver->createDirectory($logDir, 0775);
            }

            // Format log entry
            $timestamp = date('Y-m-d H:i:s');
            $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
            $logEntry = "[{$timestamp}] [{$level}] {$message}{$contextStr}" . PHP_EOL;

            // Append to log file
            $this->fileDriver->filePutContents($logFile, $logEntry, FILE_APPEND);

        } catch (\Exception $e) {
            // Silently fail - don't break the export if logging fails
        }
    }

    /**
     * Get log directory path
     *
     * @return string
     */
    public function getLogDirectory(): string
    {
        return $this->directoryList->getPath('var') . '/log/uduny';
    }

    /**
     * Get all log files
     *
     * @return array Array of log file info [filename => path]
     */
    public function getLogFiles(): array
    {
        $logDir = $this->getLogDirectory();
        $files = [];

        try {
            if ($this->fileDriver->isDirectory($logDir)) {
                $entries = $this->fileDriver->readDirectory($logDir);
                foreach ($entries as $entry) {
                    if (pathinfo($entry, PATHINFO_EXTENSION) === 'log') {
                        $filename = basename($entry);
                        $files[$filename] = [
                            'path' => $entry,
                            'filename' => $filename,
                            'size' => $this->fileDriver->stat($entry)['size'] ?? 0,
                            'modified' => $this->fileDriver->stat($entry)['mtime'] ?? 0,
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            // Return empty array on error
        }

        // Sort by filename (date) descending
        krsort($files);

        return $files;
    }

    /**
     * Read log file contents
     *
     * @param string $filename
     * @return string|null
     */
    public function readLogFile(string $filename): ?string
    {
        try {
            $logFile = $this->getLogDirectory() . '/' . basename($filename);
            if ($this->fileDriver->isExists($logFile)) {
                return $this->fileDriver->fileGetContents($logFile);
            }
        } catch (\Exception $e) {
            // Return null on error
        }

        return null;
    }

    /**
     * Delete log file
     *
     * @param string $filename
     * @return bool
     */
    public function deleteLogFile(string $filename): bool
    {
        try {
            $logFile = $this->getLogDirectory() . '/' . basename($filename);
            if ($this->fileDriver->isExists($logFile)) {
                $this->fileDriver->deleteFile($logFile);
                return true;
            }
        } catch (\Exception $e) {
            // Return false on error
        }

        return false;
    }

    /**
     * Clear all log files
     *
     * @return int Number of files deleted
     */
    public function clearAllLogs(): int
    {
        $count = 0;
        $files = $this->getLogFiles();

        foreach ($files as $file) {
            if ($this->deleteLogFile($file['filename'])) {
                $count++;
            }
        }

        return $count;
    }
}

