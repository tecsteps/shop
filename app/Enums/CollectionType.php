<?php

namespace App\Enums;

enum CollectionType: string
{
    case Manual = 'manual';
    case Automated = 'automated';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}
