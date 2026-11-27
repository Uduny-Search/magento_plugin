<?php
/**
 * Export List Index Controller
 *
 * This controller handles: Admin → Uduny → Export Lists
 *
 * Controller naming convention:
 *   - Folder: Controller/Adminhtml/{ControllerName}/
 *   - File: {ActionName}.php
 *   - Class: {ActionName}
 *
 * URL mapping:
 *   /admin/uduny/exportlist/index
 *         │     │          │
 *         │     │          └─ Action (this file)
 *         │     └─ Controller folder
 *         └─ Route frontName
 */

declare(strict_types=1);

namespace UnusualDope\Uduny\Controller\Adminhtml\Exportlist;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\View\Result\Page;

class Index extends Action
{
    /**
     * Authorization level
     * Must match an ACL resource from acl.xml
     */
    public const ADMIN_RESOURCE = 'UnusualDope_Uduny::exportlist';

    /**
     * @var PageFactory
     */
    private PageFactory $resultPageFactory;

    /**
     * Constructor
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Execute action
     *
     * This method is called when the URL is accessed.
     * It creates and returns a page result.
     *
     * @return Page
     */
    public function execute(): Page
    {
        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();

        // Set the page title (appears in browser tab and breadcrumb)
        $resultPage->setActiveMenu('UnusualDope_Uduny::exportlist');
        $resultPage->getConfig()->getTitle()->prepend(__('Export Lists'));

        // Add breadcrumbs
        $resultPage->addBreadcrumb(__('Uduny'), __('Uduny'));
        $resultPage->addBreadcrumb(__('Export Lists'), __('Export Lists'));

        return $resultPage;
    }
}

