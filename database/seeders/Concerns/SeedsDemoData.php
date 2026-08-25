<?php

namespace Database\Seeders\Concerns;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

trait SeedsDemoData
{
    /**
     * Build a complete address array using the keys consumed by the
     * application's Address value object. A "zip" key is also kept in sync
     * with "postal_code" to match the seed data specification.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function demoAddress(array $overrides = []): array
    {
        $defaults = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'company' => '',
            'address1' => 'Hauptstrasse 1',
            'address2' => '',
            'city' => 'Berlin',
            'province' => '',
            'province_code' => '',
            'country' => 'Germany',
            'country_code' => 'DE',
            'postal_code' => '10115',
            'phone' => '',
        ];

        $address = array_merge($defaults, $overrides);
        $address['zip'] = (string) ($address['postal_code'] ?? ($address['zip'] ?? ''));

        return $address;
    }

    /**
     * Build a realistic random German address for demo customers.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function fakerGermanAddress(array $overrides = []): array
    {
        return $this->demoAddress(array_merge([
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'address1' => fake()->streetAddress(),
            'city' => fake()->city(),
            'postal_code' => fake()->postcode(),
            'phone' => '+49 '.fake()->numerify('1########'),
        ], $overrides));
    }

    /**
     * Resolve a seed date. Supports "now", null, Carbon instances and
     * relative strings such as "3 months ago".
     */
    protected function resolveDate(mixed $value): ?CarbonInterface
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return $value;
        }

        if ($value === 'now') {
            return now();
        }

        return Carbon::parse($value);
    }
}
