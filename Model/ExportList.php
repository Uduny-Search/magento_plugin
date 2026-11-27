<?php
/**
 * Export List Model
 *
 * The Model class represents a single record from the database.
 * It contains:
 *   - Getters and setters for all fields
 *   - Business logic related to this entity
 *   - Connection to ResourceModel for database operations
 *
 * Model vs ResourceModel:
 *   - Model: Business logic, data manipulation
 *   - ResourceModel: Database operations (save, load, delete)
 */

declare(strict_types=1);

namespace UnusualDope\Uduny\Model;

use Magento\Framework\Model\AbstractModel;
use UnusualDope\Uduny\Api\Data\ExportListInterface;
use UnusualDope\Uduny\Model\ResourceModel\ExportList as ExportListResource;

class ExportList extends AbstractModel implements ExportListInterface
{
    /**
     * Cache tag for this entity
     * Used for cache invalidation
     */
    public const CACHE_TAG = 'uduny_export_list';

    /**
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * @var string
     */
    protected $_eventPrefix = 'uduny_export_list';

    /**
     * Initialize resource model
     *
     * This connects the Model to its ResourceModel.
     * Called automatically by Magento.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(ExportListResource::class);
    }

    /**
     * Get identities for cache invalidation
     *
     * @return array
     */
    public function getIdentities(): array
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    // =========================================================================
    // GETTERS AND SETTERS
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getEntityId(): ?int
    {
        $id = $this->getData(self::ENTITY_ID);
        return $id !== null ? (int) $id : null;
    }

    /**
     * @inheritdoc
     */
    public function setEntityId($entityId): self
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }

    /**
     * @inheritdoc
     */
    public function getSlug(): ?string
    {
        return $this->getData(self::SLUG);
    }

    /**
     * @inheritdoc
     */
    public function setSlug(string $slug): self
    {
        return $this->setData(self::SLUG, $slug);
    }

    /**
     * @inheritdoc
     */
    public function getName(): ?string
    {
        return $this->getData(self::NAME);
    }

    /**
     * @inheritdoc
     */
    public function setName(string $name): self
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * @inheritdoc
     */
    public function getStatusFilter(): ?string
    {
        return $this->getData(self::STATUS_FILTER);
    }

    /**
     * @inheritdoc
     */
    public function setStatusFilter(string $statusFilter): self
    {
        return $this->setData(self::STATUS_FILTER, $statusFilter);
    }

    /**
     * @inheritdoc
     */
    public function getStockFilter(): ?string
    {
        return $this->getData(self::STOCK_FILTER);
    }

    /**
     * @inheritdoc
     */
    public function setStockFilter(string $stockFilter): self
    {
        return $this->setData(self::STOCK_FILTER, $stockFilter);
    }

    /**
     * @inheritdoc
     */
    public function getBrandIds(): ?string
    {
        return $this->getData(self::BRAND_IDS);
    }

    /**
     * @inheritdoc
     */
    public function setBrandIds(?string $brandIds): self
    {
        return $this->setData(self::BRAND_IDS, $brandIds);
    }

    /**
     * Get brand IDs as array
     *
     * @return array
     */
    public function getBrandIdsArray(): array
    {
        $brandIds = $this->getBrandIds();
        if (empty($brandIds)) {
            return [];
        }
        return array_filter(array_map('intval', explode(',', $brandIds)));
    }

    /**
     * Set brand IDs from array
     *
     * @param array $brandIds
     * @return self
     */
    public function setBrandIdsArray(array $brandIds): self
    {
        return $this->setBrandIds(implode(',', $brandIds));
    }

    /**
     * @inheritdoc
     */
    public function getCategoryIds(): ?string
    {
        return $this->getData(self::CATEGORY_IDS);
    }

    /**
     * @inheritdoc
     */
    public function setCategoryIds(?string $categoryIds): self
    {
        return $this->setData(self::CATEGORY_IDS, $categoryIds);
    }

    /**
     * Get category IDs as array
     *
     * @return array
     */
    public function getCategoryIdsArray(): array
    {
        $categoryIds = $this->getCategoryIds();
        if (empty($categoryIds)) {
            return [];
        }
        return array_filter(array_map('intval', explode(',', $categoryIds)));
    }

    /**
     * Set category IDs from array
     *
     * @param array $categoryIds
     * @return self
     */
    public function setCategoryIdsArray(array $categoryIds): self
    {
        return $this->setCategoryIds(implode(',', $categoryIds));
    }

    /**
     * @inheritdoc
     */
    public function getExcludeCategoryIds(): ?string
    {
        return $this->getData(self::EXCLUDE_CATEGORY_IDS);
    }

    /**
     * @inheritdoc
     */
    public function setExcludeCategoryIds(?string $excludeCategoryIds): self
    {
        return $this->setData(self::EXCLUDE_CATEGORY_IDS, $excludeCategoryIds);
    }

    /**
     * Get excluded category IDs as array
     *
     * @return array
     */
    public function getExcludeCategoryIdsArray(): array
    {
        $excludeCategoryIds = $this->getExcludeCategoryIds();
        if (empty($excludeCategoryIds)) {
            return [];
        }
        return array_filter(array_map('intval', explode(',', $excludeCategoryIds)));
    }

    /**
     * Set excluded category IDs from array
     *
     * @param array $excludeCategoryIds
     * @return self
     */
    public function setExcludeCategoryIdsArray(array $excludeCategoryIds): self
    {
        return $this->setExcludeCategoryIds(implode(',', $excludeCategoryIds));
    }

    /**
     * @inheritdoc
     */
    public function getStoreId(): ?int
    {
        $storeId = $this->getData(self::STORE_ID);
        return $storeId !== null ? (int) $storeId : null;
    }

    /**
     * @inheritdoc
     */
    public function setStoreId(int $storeId): self
    {
        return $this->setData(self::STORE_ID, $storeId);
    }

    /**
     * @inheritdoc
     */
    public function getIsActive(): bool
    {
        return (bool) $this->getData(self::IS_ACTIVE);
    }

    /**
     * @inheritdoc
     */
    public function setIsActive(bool $isActive): self
    {
        return $this->setData(self::IS_ACTIVE, $isActive);
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function getUpdatedAt(): ?string
    {
        return $this->getData(self::UPDATED_AT);
    }
}

