<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HandleGenerator
{
    /**
     * Generate a URL-safe handle unique per store on the given table.
     *
     * Slugifies the title and appends an incrementing suffix (-1, -2, ...)
     * until the handle is unique for the (store_id, handle) pair.
     */
    public static function generate(string $title, string $table, int $storeId, ?int $excludeId = null): string
    {
        $base = Str::slug($title);

        if ($base === '') {
            $base = 'untitled';
        }

        $handle = $base;
        $suffix = 0;

        while (self::handleExists($table, $storeId, $handle, $excludeId)) {
            $suffix++;
            $handle = "{$base}-{$suffix}";
        }

        return $handle;
    }

    /**
     * Check whether the handle is already taken for the store.
     */
    private static function handleExists(string $table, int $storeId, string $handle, ?int $excludeId): bool
    {
        return DB::table($table)
            ->where('store_id', $storeId)
            ->where('handle', $handle)
            ->when($excludeId !== null, fn ($query) => $query->where('id', '!=', $excludeId))
            ->exists();
    }
}
