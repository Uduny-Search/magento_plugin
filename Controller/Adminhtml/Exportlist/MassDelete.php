<?php
/**
 * Mass Delete Controller
 *
 * Handles bulk deletion of export lists from the grid.
 */

declare(strict_types=1);

namespace UnusualDope\Uduny\Controller\Adminhtml\Exportlist;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Ui\Component\MassAction\Filter;
use UnusualDope\Uduny\Model\ResourceModel\ExportList\CollectionFactory;
use UnusualDope\Uduny\Api\ExportListRepositoryInterface;

class MassDelete extends Action
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

        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $count = 0;

            foreach ($collection as $item) {
                $this->exportListRepository->delete($item);
                $count++;
            }

            $this->messageManager->addSuccessMessage(
                __('A total of %1 record(s) have been deleted.', $count)
            );
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $resultRedirect->setPath('*/*/');
    }
}

