<?php
/**
 * Save Controller
 *
 * Handles form submission for creating/updating export lists.
 */

declare(strict_types=1);

namespace UnusualDope\Uduny\Controller\Adminhtml\Exportlist;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use UnusualDope\Uduny\Api\Data\ExportListInterfaceFactory;
use UnusualDope\Uduny\Api\ExportListRepositoryInterface;
use UnusualDope\Uduny\Model\ResourceModel\ExportList as ExportListResource;

class Save extends Action
{
    /**
     * Authorization level
     */
    public const ADMIN_RESOURCE = 'UnusualDope_Uduny::exportlist';

    /**
     * @var DataPersistorInterface
     */
    private DataPersistorInterface $dataPersistor;

    /**
     * @var ExportListRepositoryInterface
     */
    private ExportListRepositoryInterface $exportListRepository;

    /**
     * @var ExportListInterfaceFactory
     */
    private ExportListInterfaceFactory $exportListFactory;

    /**
     * @var ExportListResource
     */
    private ExportListResource $resource;

    /**
     * Constructor
     *
     * @param Context $context
     * @param DataPersistorInterface $dataPersistor
     * @param ExportListRepositoryInterface $exportListRepository
     * @param ExportListInterfaceFactory $exportListFactory
     * @param ExportListResource $resource
     */
    public function __construct(
        Context $context,
        DataPersistorInterface $dataPersistor,
        ExportListRepositoryInterface $exportListRepository,
        ExportListInterfaceFactory $exportListFactory,
        ExportListResource $resource
    ) {
        parent::__construct($context);
        $this->dataPersistor = $dataPersistor;
        $this->exportListRepository = $exportListRepository;
        $this->exportListFactory = $exportListFactory;
        $this->resource = $resource;
    }

    /**
     * Execute action
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();

        if (!$data) {
            return $resultRedirect->setPath('*/*/');
        }

        // Get entity ID if editing
        $entityId = isset($data['entity_id']) && $data['entity_id'] ? (int) $data['entity_id'] : null;

        try {
            // Load existing or create new
            if ($entityId) {
                $exportList = $this->exportListRepository->getById($entityId);
            } else {
                $exportList = $this->exportListFactory->create();
            }

            // Validate slug uniqueness
            $slug = $data['slug'] ?? '';
            if ($this->resource->slugExists($slug, $entityId)) {
                throw new LocalizedException(__('An export list with this slug already exists.'));
            }

            // Set data
            $exportList->setName($data['name'] ?? '');
            $exportList->setSlug($slug);
            $exportList->setStatusFilter($data['status_filter'] ?? 'enabled');
            $exportList->setStockFilter($data['stock_filter'] ?? 'in_stock');
            $exportList->setCategoryIds($this->normalizeIdList($data['category_ids'] ?? null));
            $exportList->setExcludeCategoryIds($this->normalizeIdList($data['exclude_category_ids'] ?? null));
            $exportList->setBrandIds($this->normalizeIdList($data['brand_ids'] ?? null));
            $exportList->setStoreId((int) ($data['store_id'] ?? 0));
            $exportList->setIsActive((bool) ($data['is_active'] ?? true));

            // Save
            $this->exportListRepository->save($exportList);

            // Success message
            $this->messageManager->addSuccessMessage(__('The export list has been saved.'));

            // Clear persisted data
            $this->dataPersistor->clear('uduny_exportlist');

            // Redirect based on button clicked
            if ($this->getRequest()->getParam('back') === 'continue') {
                return $resultRedirect->setPath('*/*/edit', ['entity_id' => $exportList->getEntityId()]);
            }

            return $resultRedirect->setPath('*/*/');

        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the export list.'));
        }

        // On error, persist data and redirect back to form
        $this->dataPersistor->set('uduny_exportlist', $data);

        if ($entityId) {
            return $resultRedirect->setPath('*/*/edit', ['entity_id' => $entityId]);
        }

        return $resultRedirect->setPath('*/*/new');
    }

    /**
     * Normalize array or comma-separated string of IDs to CSV string or null
     *
     * @param mixed $value
     * @return string|null
     */
    private function normalizeIdList($value): ?string
    {
        if ($value === null || $value === '' || $value === []) {
            return null;
        }

        if (is_array($value)) {
            $ids = array_values(array_unique(array_filter(
                array_map(static function ($v): string {
                    return trim((string) $v);
                }, $value),
                'strlen'
            )));
            return !empty($ids) ? implode(',', $ids) : null;
        }

        $trimmed = trim((string) $value);
        return $trimmed !== '' ? $trimmed : null;
    }
}

