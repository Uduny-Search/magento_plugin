<?php
/**
 * Mass Status Controller
 *
 * Handles bulk enable/disable of export lists from the grid.
 */

declare(strict_types=1);

namespace UnusualDope\Uduny\Controller\Adminhtml\Exportlist;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Ui\Component\MassAction\Filter;
use UnusualDope\Uduny\Model\ResourceModel\ExportList\CollectionFactory;
use UnusualDope\Uduny\Api\ExportListRepositoryInterface;

class MassStatus extends Action
{
    /**
     * Authorization level
     */
    public const ADMIN_RESOURCE = 'UnusualDope_Uduny::exportlist';

    /**
     * @var Filter
     */
    private Filter $filter;

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $collectionFactory;

    /**
     * @var ExportListRepositoryInterface
     */
    private ExportListRepositoryInterface $exportListRepository;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param ExportListRepositoryInterface $exportListRepository
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        ExportListRepositoryInterface $exportListRepository
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
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
        $status = (bool) $this->getRequest()->getParam('status', 0);

        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $count = 0;

            foreach ($collection as $item) {
                $item->setIsActive($status);
                $this->exportListRepository->save($item);
                $count++;
            }

            $statusText = $status ? __('enabled') : __('disabled');
            $this->messageManager->addSuccessMessage(
                __('A total of %1 record(s) have been %2.', $count, $statusText)
            );
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $resultRedirect->setPath('*/*/');
    }
}

