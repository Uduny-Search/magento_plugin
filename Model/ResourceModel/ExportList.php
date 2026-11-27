<?php
/**
 * Export List Resource Model
 *
 * The Resource Model handles all database operations:
 *   - save() - INSERT or UPDATE a record
 *   - load() - SELECT a single record
 *   - delete() - DELETE a record
 *
 * It's the bridge between your Model and the database.
 *
 * Why separate Model and ResourceModel?
 *   - Separation of concerns
 *   - Model = business logic
 *   - ResourceModel = data persistence
 *   - Easier to test and maintain
 */

declare(strict_types=1);

namespace UnusualDope\Uduny\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ExportList extends AbstractDb
{
    /**
     * Table name constant
     */
    public const TABLE_NAME = 'uduny_export_list';

    /**
     * Primary key field name
     */
    public const PRIMARY_KEY = 'entity_id';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct(): void
    {
        /*
         * _init() requires two parameters:
         *   1. Table name (from db_schema.xml)
         *   2. Primary key column name
         */
        $this->_init(self::TABLE_NAME, self::PRIMARY_KEY);
    }

    /**
     * Load export list by slug
     *
     * Custom load method to find record by slug instead of ID.
     * Useful for URL-based lookups.
     *
     * @param \UnusualDope\Uduny\Model\ExportList $exportList
     * @param string $slug
     * @return $this
     */
    public function loadBySlug(\UnusualDope\Uduny\Model\ExportList $exportList, string $slug): self
    {
        $connection = $this->getConnection();

        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('slug = ?', $slug);

        $data = $connection->fetchRow($select);

        if ($data) {
            $exportList->setData($data);
        }

        return $this;
    }

    /**
     * Check if slug already exists
     *
     * @param string $slug
     * @param int|null $excludeId Exclude this ID from check (for updates)
     * @return bool
     */
    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $connection = $this->getConnection();

        $select = $connection->select()
            ->from($this->getMainTable(), ['entity_id'])
            ->where('slug = ?', $slug);

        if ($excludeId !== null) {
            $select->where('entity_id != ?', $excludeId);
        }

        return (bool) $connection->fetchOne($select);
    }
}

