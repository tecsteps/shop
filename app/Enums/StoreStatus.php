<?php

namespace App\Enums;

enum StoreStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}
