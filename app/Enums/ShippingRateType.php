<?php

namespace App\Enums;

enum ShippingRateType: string
{
    case Flat = 'flat';
    case Weight = 'weight';
    case Price = 'price';
    case Carrier = 'carrier';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}
