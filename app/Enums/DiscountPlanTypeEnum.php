<?php

namespace App\Enums;

enum DiscountPlanTypeEnum: string
{
    case GENERIC = 'generic';
    case LIMITED = 'limited';

    public static function toArray(): array
    {
        return array_column(self::cases(), 'value');
    }
}
