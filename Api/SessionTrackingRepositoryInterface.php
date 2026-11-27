<?php
/**
 * Session Tracking Repository Interface
 *
 * This interface defines the "public API" for session tracking operations.
 * Other code uses this interface, not the concrete implementation.
 *
 * Why?
 *   - Decouples code from implementation details
 *   - Allows easy testing with mock objects
 *   - Follows Magento's Service Contract pattern
 *
 * Usage:
 *   // In your class constructor:
 *   public function __construct(SessionTrackingRepositoryInterface $repository)
 *
 *   // Then use it:
 *   $session = $repository->getBySessionId('abc123');
 *   $repository->save($session);
 */

declare(strict_types=1);

namespace UnusualDope\Uduny\Api;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use UnusualDope\Uduny\Api\Data\SessionTrackingInterface;

interface SessionTrackingRepositoryInterface
{
    /**
     * Save session tracking record
     *
     * @param SessionTrackingInterface $sessionTracking
     * @return SessionTrackingInterface
     * @throws CouldNotSaveException
     */
    public function save(SessionTrackingInterface $sessionTracking): SessionTrackingInterface;

    /**
     * Get session tracking by entity ID
     *
     * @param int $entityId
     * @return SessionTrackingInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $entityId): SessionTrackingInterface;

    /**
     * Get session tracking by Magento session ID
     *
     * @param string $sessionId
     * @return SessionTrackingInterface
     * @throws NoSuchEntityException
     */
    public function getBySessionId(string $sessionId): SessionTrackingInterface;

    /**
     * Get session tracking by Uduny token
     *
     * @param string $token
     * @return SessionTrackingInterface
     * @throws NoSuchEntityException
     */
    public function getByUdunyToken(string $token): SessionTrackingInterface;

    /**
     * Delete session tracking record
     *
     * @param SessionTrackingInterface $sessionTracking
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(SessionTrackingInterface $sessionTracking): bool;

    /**
     * Delete session tracking by entity ID
     *
     * @param int $entityId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById(int $entityId): bool;

    /**
     * Get or create session tracking for current session
     *
     * If session exists, returns it. If not, creates a new one.
     *
     * @param string $sessionId
     * @param int $storeId
     * @param int|null $customerId
     * @return SessionTrackingInterface
     * @throws CouldNotSaveException
     */
    public function getOrCreate(string $sessionId, int $storeId, ?int $customerId = null): SessionTrackingInterface;
}

