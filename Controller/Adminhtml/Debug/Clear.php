<?php
/**
 * Clear All Logs Controller
 *
 * Deletes all log files in the debug folder.
 */

declare(strict_types=1);

namespace UnusualDope\Uduny\Controller\Adminhtml\Debug;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;
use UnusualDope\Uduny\Model\Logger;

class Clear extends Action
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

        $count = $this->logger->clearAllLogs();

        return $result->setData([
            'success' => true,
            'message' => __('%1 log file(s) deleted', $count),
            'count' => $count,
        ]);
    }
}

