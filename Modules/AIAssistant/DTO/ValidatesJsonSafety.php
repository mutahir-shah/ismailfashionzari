<?php

namespace Modules\AIAssistant\DTO;

use InvalidArgumentException;

trait ValidatesJsonSafety
{
    /**
     * @throws InvalidArgumentException
     */
    private static function ensureJsonPayloadSafe(array $payload): void
    {
        try {
            json_encode($payload, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new InvalidArgumentException("JSON encoding failed for payload: " . $e->getMessage());
        }

        self::traverseForUnsafeTypes($payload, 'payload');
    }

    private static function traverseForUnsafeTypes(mixed $value, string $path): void
    {
        if (is_object($value)) {
            throw new InvalidArgumentException("Invalid data at {$path}: objects are not allowed.");
        }
        if (is_resource($value)) {
            throw new InvalidArgumentException("Invalid data at {$path}: resources are not allowed.");
        }
        if (is_float($value) && (is_nan($value) || is_infinite($value))) {
            throw new InvalidArgumentException("Invalid data at {$path}: non-finite floats are not allowed.");
        }
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                self::traverseForUnsafeTypes($v, $path . '.' . $k);
            }
        }
    }
}
