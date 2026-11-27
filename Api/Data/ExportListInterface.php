<?php
/**
 * Export List Data Interface
 *
 * This interface defines the "contract" for ExportList data.
 * It's part of Magento's Service Contract pattern.
 *
 * Why use interfaces?
 *   - Decouples code from implementation
 *   - Allows different implementations (e.g., for testing)
 *   - Used by Magento's Web API automatically
 *   - Makes code more maintainable
 */

declare(strict_types=1);

namespace UnusualDope\Uduny\Api\Data;

interface ExportListInterface
{
    /**
     * Table column names as constants
     * Using constants prevents typos and enables IDE autocomplete
     */
    public const ENTITY_ID = 'entity_id';
    public const SLUG = 'slug';
    public const NAME = 'name';
    public const STATUS_FILTER = 'status_filter';
    public const STOCK_FILTER = 'stock_filter';
    public const BRAND_IDS = 'brand_ids';
    public const CATEGORY_IDS = 'category_ids';
    public const EXCLUDE_CATEGORY_IDS = 'exclude_category_ids';
    public const STORE_ID = 'store_id';
    public const IS_ACTIVE = 'is_active';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    /**
     * Status filter options
     */
    public const STATUS_ALL = 'all';
    public const STATUS_ENABLED = 'enabled';
    public const STATUS_DISABLED = 'disabled';

    /**
     * Stock filter options
     */
    public const STOCK_ALL = 'all';
    public const STOCK_IN_STOCK = 'in_stock';
    public const STOCK_IN_STOCK_BACKORDER = 'in_stock_backorder';

    /**
     * Get entity ID
     *
     * @return int|null
     */
    public function getEntityId(): ?int;

    /**
     * Set entity ID
     *
     * @param int $entityId
     * @return self
     */
    public function setEntityId(int $entityId): self;

    /**
     * Get URL slug
     *
     * @return string|null
     */
    public function getSlug(): ?string;

    /**
     * Set URL slug
     *
     * @param string $slug
     * @return self
     */
    public function setSlug(string $slug): self;

    /**
     * Get export list name
     *
     * @return string|null
     */
    public function getName(): ?string;

    /**
     * Set export list name
     *
     * @param string $name
     * @return self
     */
    public function setName(string $name): self;

    /**
     * Get status filter
     *
     * @return string|null
     */
    public function getStatusFilter(): ?string;

    /**
     * Set status filter
     *
     * @param string $statusFilter
     * @return self
     */
    public function setStatusFilter(string $statusFilter): self;

    /**
     * Get stock filter
     *
     * @return string|null
     */
    public function getStockFilter(): ?string;

    /**
     * Set stock filter
     *
     * @param string $stockFilter
     * @return self
     */
    public function setStockFilter(string $stockFilter): self;

    /**
     * Get brand IDs (comma-separated string)
     *
     * @return string|null
     */
    public function getBrandIds(): ?string;

    /**
     * Set brand IDs (comma-separated string)
     *
     * @param string|null $brandIds
     * @return self
     */
    public function setBrandIds(?string $brandIds): self;

    /**
     * Get category IDs (comma-separated string)
     *
     * @return string|null
     */
    public function getCategoryIds(): ?string;

    /**
     * Set category IDs (comma-separated string)
     *
     * @param string|null $categoryIds
     * @return self
     */
    public function setCategoryIds(?string $categoryIds): self;

    /**
     * Get excluded category IDs (comma-separated string)
     *
     * @return string|null
     */
    public function getExcludeCategoryIds(): ?string;

    /**
     * Set excluded category IDs (comma-separated string)
     *
     * @param string|null $excludeCategoryIds
     * @return self
     */
    public function setExcludeCategoryIds(?string $excludeCategoryIds): self;

    /**
     * Get store ID
     *
     * @return int|null
     */
    public function getStoreId(): ?int;

    /**
     * Set store ID
     *
     * @param int $storeId
     * @return self
     */
    public function setStoreId(int $storeId): self;

    /**
     * Check if active
     *
     * @return bool
     */
    public function getIsActive(): bool;

    /**
     * Set active status
     *
     * @param bool $isActive
     * @return self
     */
    public function setIsActive(bool $isActive): self;

    /**
     * Get created at timestamp
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * Get updated at timestamp
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string;
}

