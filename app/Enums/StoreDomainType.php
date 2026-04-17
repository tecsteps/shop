<?php

namespace App\Enums;

enum StoreDomainType: string
{
    case Storefront = 'storefront';
    case Admin = 'admin';
    case Api = 'api';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}
