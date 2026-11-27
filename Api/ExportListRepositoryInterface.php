<?php
/**
 * Export List Repository Interface
 *
 * The Repository pattern is the recommended way to access data in Magento 2.
 * It provides a clean API for CRUD operations.
 *
 * Benefits:
 *   - Hides implementation details
 *   - Easy to mock for testing
 *   - Automatically exposed via REST/GraphQL API
 *   - Consistent error handling
 */

declare(strict_types=1);

namespace UnusualDope\Uduny\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use UnusualDope\Uduny\Api\Data\ExportListInterface;

interface ExportListRepositoryInterface
{
    /**
     * Get export list by ID
     *
     * @param int $entityId
     * @return ExportListInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $entityId): ExportListInterface;

    /**
     * Get export list by slug
     *
     * @param string $slug
     * @return ExportListInterface
     * @throws NoSuchEntityException
     */
    public function getBySlug(string $slug): ExportListInterface;

    /**
     * Save export list
     *
     * @param ExportListInterface $exportList
     * @return ExportListInterface
     * @throws CouldNotSaveException
     */
    public function save(ExportListInterface $exportList): ExportListInterface;

    /**
     * Delete export list
     *
     * @param ExportListInterface $exportList
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(ExportListInterface $exportList): bool;

    /**
     * Delete export list by ID
     *
     * @param int $entityId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById(int $entityId): bool;

    /**
     * Get list of export lists matching criteria
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return \Magento\Framework\Api\SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria);
}

