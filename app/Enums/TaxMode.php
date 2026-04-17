<?php

namespace App\Enums;

enum TaxMode: string
{
    case Manual = 'manual';
    case Provider = 'provider';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}
