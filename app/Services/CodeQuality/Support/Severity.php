<?php

namespace App\Services\CodeQuality\Support;

class Severity
{
    /**
     * @var array<string, int>
     */
    private const ORDER = [
        'info' => 0,
        'warning' => 1,
        'error' => 2,
        'critical' => 3,
    ];

    public static function normalize(string $severity): string
    {
        $severity = mb_strtolower($severity);

        return array_key_exists($severity, self::ORDER) ? $severity : 'warning';
    }

    public static function isAtLeast(string $severity, string $threshold): bool
    {
        $severity = self::normalize($severity);
        $threshold = self::normalize($threshold);

        return self::ORDER[$severity] >= self::ORDER[$threshold];
    }

    public static function blocks(string $source, string $severity, string $confidence, string $threshold): bool
    {
        if (! self::isAtLeast($severity, $threshold)) {
            return false;
        }

        if ($source !== 'ai') {
            return true;
        }

        return in_array($severity, ['critical', 'error'], true) && $confidence === 'high';
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_keys(self::ORDER);
    }
}
