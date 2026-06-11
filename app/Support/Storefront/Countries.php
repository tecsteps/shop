<?php

namespace App\Support\Storefront;

/**
 * The storefront's supported shipping countries. Shared between the
 * address form component and the account address book.
 */
class Countries
{
    /** @var array<string, string> */
    public const array OPTIONS = [
        'DE' => 'Germany',
        'AT' => 'Austria',
        'BE' => 'Belgium',
        'FR' => 'France',
        'IT' => 'Italy',
        'NL' => 'Netherlands',
        'ES' => 'Spain',
        'GB' => 'United Kingdom',
        'US' => 'United States',
    ];

    public static function name(string $code): string
    {
        return self::OPTIONS[strtoupper($code)] ?? $code;
    }
}
