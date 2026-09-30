<?php

namespace App\Enums;

enum RewardPointTypeEnum: string
{
    case MANUAL = 'manual';
    case AUTOMATIC = 'automatic';

    public static function toArray(): array
    {
        return array_column(self::cases(), 'value');
    }
}
