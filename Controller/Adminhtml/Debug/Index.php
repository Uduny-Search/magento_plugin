<?php
/**
 * Debug Logs Index Controller
 *
 * Displays the debug logs management page.
 */

declare(strict_types=1);

namespace UnusualDope\Uduny\Controller\Adminhtml\Debug;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\View\Result\Page;

class Index extends Action
{
    /**
     * Authorization level
     */
    public const ADMIN_RESOURCE = 'UnusualDope_Uduny::debug';

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
     * @return Page
     */
    public function execute(): Page
    {
        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();

        $resultPage->setActiveMenu('UnusualDope_Uduny::debug');
        $resultPage->getConfig()->getTitle()->prepend(__('Debug Logs'));

        $resultPage->addBreadcrumb(__('Uduny'), __('Uduny'));
        $resultPage->addBreadcrumb(__('Debug Logs'), __('Debug Logs'));

        return $resultPage;
    }
}
