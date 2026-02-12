<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Support\Str;

abstract class AdminController extends Controller
{
    protected function currentStore(): Store
    {
        /** @var Store $store */
        $store = app('current_store');

        return $store;
    }

    protected function formatMoney(int $amount, string $currency): string
    {
        return sprintf('%s %s', strtoupper($currency), number_format($amount / 100, 2));
    }

    /**
     * @param  callable(string): bool  $exists
     */
    protected function uniqueHandle(string $source, callable $exists, string $fallback = 'item'): string
    {
        $base = Str::slug($source);

        if ($base === '') {
            $base = $fallback;
        }

        $handle = $base;
        $suffix = 1;

        while ($exists($handle)) {
            $handle = sprintf('%s-%d', $base, $suffix);
            $suffix++;
        }

        return $handle;
    }

    /**
     * @return list<string>
     */
    protected function enumValues(string $enumClass): array
    {
        return array_map(static fn ($case): string => $case->value, $enumClass::cases());
    }
}
