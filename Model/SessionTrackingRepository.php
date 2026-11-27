<?php
/**
 * Session Tracking Repository
 *
 * This is the concrete implementation of SessionTrackingRepositoryInterface.
 * It provides the actual logic for saving, loading, and deleting sessions.
 *
 * The Repository pattern:
 *   - Abstracts the data layer from business logic
 *   - Provides a clean API for CRUD operations
 *   - Handles exceptions and error states
 */

declare(strict_types=1);

namespace UnusualDope\Uduny\Model;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use UnusualDope\Uduny\Api\Data\SessionTrackingInterface;
use UnusualDope\Uduny\Api\Data\SessionTrackingInterfaceFactory;
use UnusualDope\Uduny\Api\SessionTrackingRepositoryInterface;
use UnusualDope\Uduny\Model\ResourceModel\SessionTracking as SessionTrackingResource;

class SessionTrackingRepository implements SessionTrackingRepositoryInterface
{
    /**
     * @var SessionTrackingResource
     */
    private SessionTrackingResource $resource;

    /**
     * @var SessionTrackingInterfaceFactory
     */
    private SessionTrackingInterfaceFactory $sessionTrackingFactory;

    /**
     * Constructor
     *
     * @param SessionTrackingResource $resource
     * @param SessionTrackingInterfaceFactory $sessionTrackingFactory
     */
    public function __construct(
        SessionTrackingResource $resource,
        SessionTrackingInterfaceFactory $sessionTrackingFactory
    ) {
        $this->resource = $resource;
        $this->sessionTrackingFactory = $sessionTrackingFactory;
    }

    /**
     * @inheritdoc
     */
    public function save(SessionTrackingInterface $sessionTracking): SessionTrackingInterface
    {
        try {
            $this->resource->save($sessionTracking);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(
                __('Could not save session tracking: %1', $e->getMessage()),
                $e
            );
        }

        return $sessionTracking;
    }

    /**
     * @inheritdoc
     */
    public function getById(int $entityId): SessionTrackingInterface
    {
        $sessionTracking = $this->sessionTrackingFactory->create();
        $this->resource->load($sessionTracking, $entityId);

        if (!$sessionTracking->getEntityId()) {
            throw new NoSuchEntityException(
                __('Session tracking with ID "%1" does not exist.', $entityId)
            );
        }

        return $sessionTracking;
    }

    /**
     * @inheritdoc
     */
    public function getBySessionId(string $sessionId): SessionTrackingInterface
    {
        $sessionTracking = $this->sessionTrackingFactory->create();
        $this->resource->loadBySessionId($sessionTracking, $sessionId);

        if (!$sessionTracking->getEntityId()) {
            throw new NoSuchEntityException(
                __('Session tracking with session ID "%1" does not exist.', $sessionId)
            );
        }

        return $sessionTracking;
    }

    /**
     * @inheritdoc
     */
    public function getByUdunyToken(string $token): SessionTrackingInterface
    {
        $sessionTracking = $this->sessionTrackingFactory->create();
        $this->resource->loadByUdunyToken($sessionTracking, $token);

        if (!$sessionTracking->getEntityId()) {
            throw new NoSuchEntityException(
                __('Session tracking with token "%1" does not exist.', $token)
            );
        }

        return $sessionTracking;
    }

    /**
     * @inheritdoc
     */
    public function delete(SessionTrackingInterface $sessionTracking): bool
    {
        try {
            $this->resource->delete($sessionTracking);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(
                __('Could not delete session tracking: %1', $e->getMessage()),
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
        return $this->delete($this->getById($entityId));
    }

    /**
     * @inheritdoc
     */
    public function getOrCreate(string $sessionId, int $storeId, ?int $customerId = null): SessionTrackingInterface
    {
        try {
            // Try to find existing session
            $sessionTracking = $this->getBySessionId($sessionId);

            // Update customer ID if provided and different
            if ($customerId !== null && $sessionTracking->getCustomerId() !== $customerId) {
                $sessionTracking->setCustomerId($customerId);
                $this->save($sessionTracking);
            }

            return $sessionTracking;

        } catch (NoSuchEntityException $e) {
            // Session doesn't exist, create new one
            $sessionTracking = $this->sessionTrackingFactory->create();
            $sessionTracking->setSessionId($sessionId);
            $sessionTracking->setStoreId($storeId);
            $sessionTracking->setCustomerId($customerId);

            // Generate a unique Uduny token
            $sessionTracking->setUdunyToken($this->generateUdunyToken());

            return $this->save($sessionTracking);
        }
    }

    /**
     * Generate a dummy Uduny token
     *
     * For now, just use a simple format: uduny_[timestamp]_[random4chars]
     * This can be replaced with a real token from Uduny API later
     *
     * @return string
     */
    private function generateUdunyToken(): string
    {
        // Simple dummy token: uduny_timestamp_random
        return 'uduny_' . time() . '_' . substr(md5((string) mt_rand()), 0, 8);
    }
}

