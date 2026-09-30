<?php

namespace Modules\AIAssistant\DTO;

use InvalidArgumentException;

/**
 * Value object representing warehouse access scope derived from AssistantContextData.
 */
final readonly class WarehouseScope
{
    /**
     * @param bool $isRestricted If false, access is unrestricted/admin. If true, restricted to $warehouseIds.
     * @param array $warehouseIds The list of allowed positive integer warehouse IDs. Empty means no access.
     * @param int|null $ownUserId If present, restricts access to rows owned by this user (staff_access = 'own').
     */
    public function __construct(
        public bool $isRestricted,
        public array $warehouseIds = [],
        public ?int $ownUserId = null
    ) {}

    /**
     * Parse the warehouse_ids and own_user_id scope from the provided context.
     * 
     * Rules:
     * - key absent: unrestricted/admin-compatible
     * - key present: must be a list of positive integer IDs
     * - duplicate IDs are normalized deterministically
     * - empty list means no warehouse access
     * - malformed values throw/fail closed
     *
     * @param AssistantContextData $context
     * @return self
     * @throws InvalidArgumentException
     */
    public static function fromContext(AssistantContextData $context): self
    {
        $ownUserId = null;
        if (array_key_exists('own_user_id', $context->businessContext)) {
            $val = $context->businessContext['own_user_id'];
            if (!is_int($val) || $val <= 0) {
                throw new InvalidArgumentException("Malformed own_user_id scope: must be a positive integer.");
            }
            $ownUserId = $val;
        }

        if (!array_key_exists('warehouse_ids', $context->businessContext)) {
            return new self(isRestricted: false, ownUserId: $ownUserId);
        }

        $ids = $context->businessContext['warehouse_ids'];

        if (!is_array($ids)) {
            throw new InvalidArgumentException("Malformed warehouse_ids scope: must be an array.");
        }
        
        // Reject associative arrays
        if (!empty($ids) && array_keys($ids) !== range(0, count($ids) - 1)) {
            throw new InvalidArgumentException("Malformed warehouse_ids scope: must be a list array.");
        }

        $cleanIds = [];
        foreach ($ids as $id) {
            if (!is_int($id) || $id <= 0) {
                throw new InvalidArgumentException("Malformed warehouse_ids scope: must contain only positive integers.");
            }
            $cleanIds[] = $id;
        }

        // Normalize deterministically
        $cleanIds = array_values(array_unique($cleanIds));
        sort($cleanIds);

        return new self(isRestricted: true, warehouseIds: $cleanIds, ownUserId: $ownUserId);
    }
}
