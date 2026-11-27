<?php
/**
 * Delete Controller
 *
 * Handles deletion of a single export list.
 */

declare(strict_types=1);

namespace UnusualDope\Uduny\Controller\Adminhtml\Exportlist;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use UnusualDope\Uduny\Api\ExportListRepositoryInterface;

class Delete extends Action
{
    /**
     * Authorization level
     */
    public const ADMIN_RESOURCE = 'UnusualDope_Uduny::exportlist';

    /**
     * @var ExportListRepositoryInterface
     */
    private ExportListRepositoryInterface $exportListRepository;

    /**
     * Constructor
     *
     * @param Context $context
     * @param ExportListRepositoryInterface $exportListRepository
     */
    public function __construct(
        Context $context,
        ExportListRepositoryInterface $exportListRepository
    ) {
        parent::__construct($context);
        $this->exportListRepository = $exportListRepository;
    }

    /**
     * Execute action
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $entityId = (int) $this->getRequest()->getParam('entity_id');

        if (!$entityId) {
            $this->messageManager->addErrorMessage(__('We can\'t find the export list to delete.'));
            return $resultRedirect->setPath('*/*/');
        }

        try {
            $this->exportListRepository->deleteById($entityId);
            $this->messageManager->addSuccessMessage(__('The export list has been deleted.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $resultRedirect->setPath('*/*/');
    }
}

