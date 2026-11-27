<?php
/**
 * Refresh Logs Controller
 *
 * Returns the current list of log files via AJAX.
 */

declare(strict_types=1);

namespace UnusualDope\Uduny\Controller\Adminhtml\Debug;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;
use UnusualDope\Uduny\Model\Logger;

class Refresh extends Action
{
    /**
     * Authorization level
     */
    public const ADMIN_RESOURCE = 'UnusualDope_Uduny::debug';

    /**
     * @var JsonFactory
     */
    private JsonFactory $jsonFactory;

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * Constructor
     *
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        Logger $logger
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->logger = $logger;
    }

    /**
     * Execute action
     *
     * @return Json
     */
    public function execute(): Json
    {
        $result = $this->jsonFactory->create();

        $files = $this->logger->getLogFiles();

        // Format file sizes
        $formattedFiles = [];
        foreach ($files as $filename => $info) {
            $formattedFiles[] = [
                'filename' => $info['filename'],
                'size' => $this->formatBytes($info['size']),
                'size_raw' => $info['size'],
                'modified' => date('Y-m-d H:i:s', $info['modified']),
            ];
        }

        return $result->setData([
            'success' => true,
            'files' => $formattedFiles,
            'count' => count($formattedFiles),
        ]);
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

