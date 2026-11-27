<?php
/**
 * View Log Controller
 *
 * Returns the contents of a specific log file via AJAX.
 */

declare(strict_types=1);

namespace UnusualDope\Uduny\Controller\Adminhtml\Debug;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;
use UnusualDope\Uduny\Model\Logger;

class View extends Action
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
        $filename = $this->getRequest()->getParam('filename');

        if (!$filename) {
            return $result->setData([
                'success' => false,
                'message' => __('Filename is required'),
            ]);
        }

        $contents = $this->logger->readLogFile($filename);

        if ($contents === null) {
            return $result->setData([
                'success' => false,
                'message' => __('Log file not found'),
            ]);
        }

        return $result->setData([
            'success' => true,
            'filename' => $filename,
            'contents' => $contents,
        ]);
    }
}

