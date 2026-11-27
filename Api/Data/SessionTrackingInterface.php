<?php
/**
 * Session Tracking Data Interface
 *
 * This interface defines the "contract" for SessionTracking data.
 * It lists all the fields and their getters/setters.
 *
 * Think of it as a blueprint that says:
 * "Any class that represents a session tracking record must have these methods"
 */

declare(strict_types=1);

namespace UnusualDope\Uduny\Api\Data;

interface SessionTrackingInterface
{
    /**
     * Database column names as constants
     * Using constants prevents typos and enables IDE autocomplete
     */
    public const ENTITY_ID = 'entity_id';
    public const SESSION_ID = 'session_id';
    public const CUSTOMER_ID = 'customer_id';
    public const UDUNY_TOKEN = 'uduny_token';
    public const STORE_ID = 'store_id';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

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
     * Get Magento session ID
     *
     * @return string|null
     */
    public function getSessionId(): ?string;

    /**
     * Set Magento session ID
     *
     * @param string $sessionId
     * @return self
     */
    public function setSessionId(string $sessionId): self;

    /**
     * Get customer ID (null if guest)
     *
     * @return int|null
     */
    public function getCustomerId(): ?int;

    /**
     * Set customer ID
     *
     * @param int|null $customerId
     * @return self
     */
    public function setCustomerId(?int $customerId): self;

    /**
     * Get Uduny token
     *
     * @return string|null
     */
    public function getUdunyToken(): ?string;

    /**
     * Set Uduny token
     *
     * @param string|null $udunyToken
     * @return self
     */
    public function setUdunyToken(?string $udunyToken): self;

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

