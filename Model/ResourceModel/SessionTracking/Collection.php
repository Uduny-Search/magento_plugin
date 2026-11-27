<?php
/**
 * Session Tracking Collection
 *
 * A Collection is used to load multiple records from the database.
 * Think of it as a "query builder" that returns an array of Models.
 *
 * Usage:
 *   $collection = $collectionFactory->create();
 *   $collection->addFieldToFilter('customer_id', 123);
 *   foreach ($collection as $session) {
 *       echo $session->getSessionId();
 *   }
 */

declare(strict_types=1);

namespace UnusualDope\Uduny\Model\ResourceModel\SessionTracking;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use UnusualDope\Uduny\Model\SessionTracking as SessionTrackingModel;
use UnusualDope\Uduny\Model\ResourceModel\SessionTracking as SessionTrackingResource;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * Initialize collection
     *
     * Links the Model to the ResourceModel
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(SessionTrackingModel::class, SessionTrackingResource::class);
    }

    /**
     * Filter by customer ID
     *
     * @param int $customerId
     * @return $this
     */
    public function filterByCustomerId(int $customerId): self
    {
        $this->addFieldToFilter('customer_id', $customerId);
        return $this;
    }

    /**
     * Filter by store ID
     *
     * @param int $storeId
     * @return $this
     */
    public function filterByStoreId(int $storeId): self
    {
        $this->addFieldToFilter('store_id', $storeId);
        return $this;
    }

    /**
     * Filter by sessions created in the last X hours
     *
     * @param int $hours
     * @return $this
     */
    public function filterRecentSessions(int $hours = 24): self
    {
        $this->addFieldToFilter(
            'created_at',
            ['gteq' => new \Zend_Db_Expr("DATE_SUB(NOW(), INTERVAL {$hours} HOUR)")]
        );
        return $this;
    }
}

