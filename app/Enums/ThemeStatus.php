<?php

namespace App\Enums;

enum ThemeStatus: string
{
    case Draft = 'draft';
    case Published = 'published';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}
