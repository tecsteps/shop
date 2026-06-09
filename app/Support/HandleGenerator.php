<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HandleGenerator
{
    /**
     * Generate a URL-safe handle from the title, unique per store within the
     * given table. On collision an incrementing suffix is appended
     * (my-product, my-product-1, my-product-2, ...).
     */
    public function generate(string $title, string $table, int $storeId, ?int $excludeId = null): string
    {
        $base = Str::slug($title);

        if ($base === '') {
            $base = 'untitled';
        }

        $handle = $base;
        $suffix = 0;

        while ($this->handleExists($handle, $table, $storeId, $excludeId)) {
            $suffix++;
            $handle = "{$base}-{$suffix}";
        }

        return $handle;
    }

    private function handleExists(string $handle, string $table, int $storeId, ?int $excludeId): bool
    {
        return DB::table($table)
            ->where('store_id', $storeId)
            ->where('handle', $handle)
            ->when($excludeId !== null, fn ($query) => $query->where('id', '!=', $excludeId))
            ->exists();
    }
}
