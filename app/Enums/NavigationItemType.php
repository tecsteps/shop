<?php

namespace App\Enums;

enum NavigationItemType: string
{
    case Link = 'link';
    case Page = 'page';
    case Collection = 'collection';
    case Product = 'product';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}
