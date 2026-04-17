<?php

namespace App\Enums;

enum InventoryPolicy: string
{
    case Deny = 'deny';
    case Continue = 'continue';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}
