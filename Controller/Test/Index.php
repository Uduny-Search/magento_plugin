<?php
/**
 * Simple Test Controller
 *
 * URL: /uduny/test/index or /uduny/test
 *
 * This is a minimal controller to test if routing works.
 */

declare(strict_types=1);

namespace UnusualDope\Uduny\Controller\Test;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;

class Index implements HttpGetActionInterface
{
    /**
     * @var JsonFactory
     */
    private JsonFactory $jsonFactory;

    /**
     * Constructor
     *
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        JsonFactory $jsonFactory
    ) {
        $this->jsonFactory = $jsonFactory;
    }

    /**
     * Execute action
     *
     * @return Json
     */
    public function execute(): Json
    {
        $result = $this->jsonFactory->create();

        return $result->setData([
            'success' => true,
            'message' => 'Uduny module is working!',
            'timestamp' => date('Y-m-d H:i:s'),
        ]);
    }
}

