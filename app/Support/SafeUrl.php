<?php

namespace App\Support;

final class SafeUrl
{
    public static function isAllowed(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (! is_string($value)) {
            return false;
        }

        $url = trim($value);
        if ($url === '' || str_starts_with($url, '//') || str_contains($url, '\\')) {
            return $url === '';
        }

        $decoded = $url;
        for ($iteration = 0; $iteration < 2; $iteration++) {
            $decoded = rawurldecode($decoded);
        }

        if (preg_match('/[\x00-\x20\x7f]/', $decoded) === 1) {
            return false;
        }

        $scheme = parse_url($decoded, PHP_URL_SCHEME);
        if (is_string($scheme) && $scheme !== '') {
            if (! in_array(mb_strtolower($scheme), ['http', 'https'], true)) {
                return false;
            }

            return is_string(parse_url($decoded, PHP_URL_HOST)) && parse_url($decoded, PHP_URL_HOST) !== '';
        }

        return parse_url($decoded) !== false;
    }

    public static function normalize(mixed $value): ?string
    {
        if (! self::isAllowed($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /** @param array<string, mixed> $settings @return array<string, mixed> */
    public static function sanitizeThemeSettings(array $settings): array
    {
        foreach ([
            'announcement.url',
            'home.hero.cta_url',
            'home.hero.image_url',
            'header.logo_url',
        ] as $key) {
            if (data_get($settings, $key) !== null) {
                data_set($settings, $key, self::normalize(data_get($settings, $key)));
            }
        }

        return $settings;
    }
}
