<?php
/**
 * Edit Controller
 *
 * Displays the form for editing an existing export list.
 */

declare(strict_types=1);

namespace UnusualDope\Uduny\Controller\Adminhtml\Exportlist;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\View\Result\Page;
use Magento\Framework\Controller\Result\Redirect;
use UnusualDope\Uduny\Api\ExportListRepositoryInterface;

class Edit extends Action
{
    /**
     * Authorization level
     */
    public const ADMIN_RESOURCE = 'UnusualDope_Uduny::exportlist';

    /**
     * @var PageFactory
     */
    private PageFactory $resultPageFactory;

    /**
     * @var ExportListRepositoryInterface
     */
    private ExportListRepositoryInterface $exportListRepository;

    /**
     * Constructor
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param ExportListRepositoryInterface $exportListRepository
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        ExportListRepositoryInterface $exportListRepository
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->exportListRepository = $exportListRepository;
    }

    /**
     * Execute action
     *
     * @return Page|Redirect
     */
    public function execute()
    {
        $entityId = (int) $this->getRequest()->getParam('entity_id');

        // If editing, verify the record exists
        if ($entityId) {
            try {
                $exportList = $this->exportListRepository->getById($entityId);
                $title = __('Edit: %1', $exportList->getName());
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('This export list no longer exists.'));
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        } else {
            $title = __('New Export List');
        }

        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('UnusualDope_Uduny::exportlist');
        $resultPage->getConfig()->getTitle()->prepend($title);

        return $resultPage;
    }
}

