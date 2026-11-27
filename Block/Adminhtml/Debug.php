<?php
/**
 * Debug Block
 *
 * Block class for the debug logs page.
 * Provides data and URLs for the template.
 */

declare(strict_types=1);

namespace UnusualDope\Uduny\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use UnusualDope\Uduny\Model\Config;
use UnusualDope\Uduny\Model\Logger;

class Debug extends Template
{
    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Logger $logger
     * @param Config $config
     * @param array $data
     */
    public function __construct(
        Context $context,
        Logger $logger,
        Config $config,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * Get log files
     *
     * @return array
     */
    public function getLogFiles(): array
    {
        $files = $this->logger->getLogFiles();

        // Format for display
        $formatted = [];
        foreach ($files as $filename => $info) {
            $formatted[] = [
                'filename' => $info['filename'],
                'size' => $this->formatBytes($info['size']),
                'size_raw' => $info['size'],
                'modified' => date('Y-m-d H:i:s', $info['modified']),
            ];
        }

        return $formatted;
    }

    /**
     * Check if debug mode is enabled
     *
     * @return bool
     */
    public function isDebugModeEnabled(): bool
    {
        return $this->config->isDebugMode();
    }

    /**
     * Get log directory path
     *
     * @return string
     */
    public function getLogDirectory(): string
    {
        return $this->logger->getLogDirectory();
    }

    /**
     * Get refresh URL
     *
     * @return string
     */
    public function getRefreshUrl(): string
    {
        return $this->getUrl('uduny/debug/refresh');
    }

    /**
     * Get view URL
     *
     * @return string
     */
    public function getViewUrl(): string
    {
        return $this->getUrl('uduny/debug/view');
    }

    /**
     * Get delete URL
     *
     * @return string
     */
    public function getDeleteUrl(): string
    {
        return $this->getUrl('uduny/debug/delete');
    }

    /**
     * Get clear all URL
     *
     * @return string
     */
    public function getClearUrl(): string
    {
        return $this->getUrl('uduny/debug/clear');
    }

    /**
     * Format bytes to human readable string
     *
     * @param int $bytes
     * @return string
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen((string) $bytes) - 1) / 3);
        return sprintf("%.1f %s", $bytes / pow(1024, $factor), $units[$factor] ?? 'B');
    }
}

