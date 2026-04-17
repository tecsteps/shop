<?php

namespace App\Enums;

enum TaxProviderType: string
{
    case None = 'none';
    case StripeTax = 'stripe_tax';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}
