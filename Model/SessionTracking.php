<?php
/**
 * Session Tracking Model
 *
 * This class represents a single record from uduny_session_tracking table.
 * It implements the interface and provides getters/setters.
 *
 * Usage:
 *   $session = $sessionTrackingFactory->create();
 *   $session->setSessionId('abc123');
 *   $session->setCustomerId(456);
 *   $repository->save($session);
 */

declare(strict_types=1);

namespace UnusualDope\Uduny\Model;

use Magento\Framework\Model\AbstractModel;
use UnusualDope\Uduny\Api\Data\SessionTrackingInterface;
use UnusualDope\Uduny\Model\ResourceModel\SessionTracking as SessionTrackingResource;

class SessionTracking extends AbstractModel implements SessionTrackingInterface
{
    /**
     * Cache tag for this entity
     */
    public const CACHE_TAG = 'uduny_session_tracking';

    /**
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * @var string
     */
    protected $_eventPrefix = 'uduny_session_tracking';

    /**
     * Initialize resource model
     *
     * This connects the Model to its ResourceModel (database layer)
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(SessionTrackingResource::class);
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
    public function getSessionId(): ?string
    {
        return $this->getData(self::SESSION_ID);
    }

    /**
     * @inheritdoc
     */
    public function setSessionId(string $sessionId): self
    {
        return $this->setData(self::SESSION_ID, $sessionId);
    }

    /**
     * @inheritdoc
     */
    public function getCustomerId(): ?int
    {
        $id = $this->getData(self::CUSTOMER_ID);
        return $id !== null ? (int) $id : null;
    }

    /**
     * @inheritdoc
     */
    public function setCustomerId(?int $customerId): self
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * @inheritdoc
     */
    public function getUdunyToken(): ?string
    {
        return $this->getData(self::UDUNY_TOKEN);
    }

    /**
     * @inheritdoc
     */
    public function setUdunyToken(?string $udunyToken): self
    {
        return $this->setData(self::UDUNY_TOKEN, $udunyToken);
    }

    /**
     * @inheritdoc
     */
    public function getStoreId(): ?int
    {
        $id = $this->getData(self::STORE_ID);
        return $id !== null ? (int) $id : null;
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

