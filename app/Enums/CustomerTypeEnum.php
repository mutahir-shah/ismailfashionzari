<?php

namespace App\Enums;

enum CustomerTypeEnum: string
{
    case REGULAR = 'regular';
    case WALKIN = 'walkin';

    public static function toArray(): array
    {
        return array_column(self::cases(), 'value');
    }
}
