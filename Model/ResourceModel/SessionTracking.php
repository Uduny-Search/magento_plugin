<?php
/**
 * Session Tracking Resource Model
 *
 * The ResourceModel is the "database layer" - it handles:
 *   - Connecting to the database table
 *   - Saving records
 *   - Loading records
 *   - Deleting records
 *
 * Model vs ResourceModel:
 *   - Model: Business logic, data manipulation
 *   - ResourceModel: Raw database operations
 */

declare(strict_types=1);

namespace UnusualDope\Uduny\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class SessionTracking extends AbstractDb
{
    /**
     * Table name constant
     */
    public const TABLE_NAME = 'uduny_session_tracking';

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
        // _init(table_name, primary_key_column)
        $this->_init(self::TABLE_NAME, self::PRIMARY_KEY);
    }

    /**
     * Load session tracking by Magento session ID
     *
     * @param \UnusualDope\Uduny\Model\SessionTracking $object
     * @param string $sessionId
     * @return $this
     */
    public function loadBySessionId($object, string $sessionId): self
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('session_id = ?', $sessionId);

        $data = $connection->fetchRow($select);

        if ($data) {
            $object->setData($data);
        }

        return $this;
    }

    /**
     * Load session tracking by Uduny token
     *
     * @param \UnusualDope\Uduny\Model\SessionTracking $object
     * @param string $token
     * @return $this
     */
    public function loadByUdunyToken($object, string $token): self
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('uduny_token = ?', $token);

        $data = $connection->fetchRow($select);

        if ($data) {
            $object->setData($data);
        }

        return $this;
    }

    /**
     * Check if session ID already exists
     *
     * @param string $sessionId
     * @param int|null $excludeEntityId
     * @return bool
     */
    public function sessionExists(string $sessionId, ?int $excludeEntityId = null): bool
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable(), 'entity_id')
            ->where('session_id = ?', $sessionId);

        if ($excludeEntityId) {
            $select->where('entity_id != ?', $excludeEntityId);
        }

        return (bool) $connection->fetchOne($select);
    }
}

