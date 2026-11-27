<?php
/**
 * Export List Collection
 *
 * A Collection represents multiple records from the database.
 * It's like an enhanced array of Model objects with built-in:
 *   - Filtering (WHERE clauses)
 *   - Sorting (ORDER BY)
 *   - Pagination (LIMIT, OFFSET)
 *   - Joining other tables
 *
 * Usage examples:
 *   $collection->addFieldToFilter('is_active', true);
 *   $collection->addFieldToFilter('store_id', ['in' => [0, 1]]);
 *   $collection->setOrder('name', 'ASC');
 *   $collection->setPageSize(10)->setCurPage(1);
 *
 *   foreach ($collection as $item) {
 *       echo $item->getName();
 *   }
 */

declare(strict_types=1);

namespace UnusualDope\Uduny\Model\ResourceModel\ExportList;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use UnusualDope\Uduny\Model\ExportList as ExportListModel;
use UnusualDope\Uduny\Model\ResourceModel\ExportList as ExportListResource;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * @var string
     */
    protected $_eventPrefix = 'uduny_export_list_collection';

    /**
     * @var string
     */
    protected $_eventObject = 'export_list_collection';

    /**
     * Initialize collection
     *
     * Connects the Collection to its Model and ResourceModel.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(ExportListModel::class, ExportListResource::class);
    }

    /**
     * Filter by active status
     *
     * @param bool $isActive
     * @return $this
     */
    public function addActiveFilter(bool $isActive = true): self
    {
        return $this->addFieldToFilter('is_active', $isActive);
    }

    /**
     * Filter by store ID
     *
     * Includes store 0 (all stores) by default.
     *
     * @param int $storeId
     * @param bool $includeAllStores Include store_id = 0
     * @return $this
     */
    public function addStoreFilter(int $storeId, bool $includeAllStores = true): self
    {
        $storeIds = [$storeId];
        if ($includeAllStores) {
            $storeIds[] = 0; // 0 means "all stores"
        }

        return $this->addFieldToFilter('store_id', ['in' => $storeIds]);
    }

    /**
     * Load by slug
     *
     * @param string $slug
     * @return $this
     */
    public function addSlugFilter(string $slug): self
    {
        return $this->addFieldToFilter('slug', $slug);
    }
}

