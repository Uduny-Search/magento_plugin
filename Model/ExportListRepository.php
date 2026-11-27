<?php
/**
 * Export List Repository Implementation
 *
 * This class implements the Repository interface.
 * It's the "service layer" for accessing ExportList data.
 *
 * Usage:
 *   // In your class constructor, inject the repository:
 *   public function __construct(ExportListRepositoryInterface $exportListRepository)
 *
 *   // Then use it:
 *   $exportList = $this->exportListRepository->getById(1);
 *   $exportList->setName('New Name');
 *   $this->exportListRepository->save($exportList);
 */

declare(strict_types=1);

namespace UnusualDope\Uduny\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use UnusualDope\Uduny\Api\Data\ExportListInterface;
use UnusualDope\Uduny\Api\ExportListRepositoryInterface;
use UnusualDope\Uduny\Model\ResourceModel\ExportList as ExportListResource;
use UnusualDope\Uduny\Model\ResourceModel\ExportList\CollectionFactory;

class ExportListRepository implements ExportListRepositoryInterface
{
    /**
     * @var ExportListResource
     */
    private ExportListResource $resource;

    /**
     * @var ExportListFactory
     */
    private ExportListFactory $exportListFactory;

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $collectionFactory;

    /**
     * @var CollectionProcessorInterface
     */
    private CollectionProcessorInterface $collectionProcessor;

    /**
     * @var SearchResultsInterfaceFactory
     */
    private SearchResultsInterfaceFactory $searchResultsFactory;

    /**
     * Constructor
     *
     * @param ExportListResource $resource
     * @param ExportListFactory $exportListFactory
     * @param CollectionFactory $collectionFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param SearchResultsInterfaceFactory $searchResultsFactory
     */
    public function __construct(
        ExportListResource $resource,
        ExportListFactory $exportListFactory,
        CollectionFactory $collectionFactory,
        CollectionProcessorInterface $collectionProcessor,
        SearchResultsInterfaceFactory $searchResultsFactory
    ) {
        $this->resource = $resource;
        $this->exportListFactory = $exportListFactory;
        $this->collectionFactory = $collectionFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->searchResultsFactory = $searchResultsFactory;
    }

    /**
     * @inheritdoc
     */
    public function getById(int $entityId): ExportListInterface
    {
        $exportList = $this->exportListFactory->create();
        $this->resource->load($exportList, $entityId);

        if (!$exportList->getEntityId()) {
            throw new NoSuchEntityException(
                __('Export List with ID "%1" does not exist.', $entityId)
            );
        }

        return $exportList;
    }

    /**
     * @inheritdoc
     */
    public function getBySlug(string $slug): ExportListInterface
    {
        $exportList = $this->exportListFactory->create();
        $this->resource->loadBySlug($exportList, $slug);

        if (!$exportList->getEntityId()) {
            throw new NoSuchEntityException(
                __('Export List with slug "%1" does not exist.', $slug)
            );
        }

        return $exportList;
    }

    /**
     * @inheritdoc
     */
    public function save(ExportListInterface $exportList): ExportListInterface
    {
        try {
            $this->resource->save($exportList);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(
                __('Could not save Export List: %1', $e->getMessage()),
                $e
            );
        }

        return $exportList;
    }

    /**
     * @inheritdoc
     */
    public function delete(ExportListInterface $exportList): bool
    {
        try {
            $this->resource->delete($exportList);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(
                __('Could not delete Export List: %1', $e->getMessage()),
                $e
            );
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteById(int $entityId): bool
    {
        $exportList = $this->getById($entityId);
        return $this->delete($exportList);
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface
    {
        $collection = $this->collectionFactory->create();

        // Apply filters, sorting, and pagination from search criteria
        $this->collectionProcessor->process($searchCriteria, $collection);

        // Build search results
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }
}

