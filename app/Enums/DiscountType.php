<?php

namespace App\Enums;

enum DiscountType: string
{
    case Code = 'code';
    case Automatic = 'automatic';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}
