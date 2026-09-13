<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Generates URL-safe handles unique per store (spec 05 section 2.4).
 */
class HandleGenerator
{
    public function generate(string $title, string $table, int $storeId, ?int $excludeId = null): string
    {
        $base = Str::slug($title);

        if ($base === '') {
            $base = 'item';
        }

        $handle = $base;
        $suffix = 0;

        while ($this->exists($table, $storeId, $handle, $excludeId)) {
            $suffix++;
            $handle = "{$base}-{$suffix}";
        }

        return $handle;
    }

    public function exists(string $table, int $storeId, string $handle, ?int $excludeId = null): bool
    {
        $query = DB::table($table)->where('store_id', $storeId)->where('handle', $handle);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
