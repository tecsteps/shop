<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HandleGenerator
{
    public static function generate(string $title, string $table, int $storeId, ?int $excludeId = null): string
    {
        $base = Str::slug($title) ?: 'item';
        $handle = $base;
        $suffix = 1;

        while (self::exists($handle, $table, $storeId, $excludeId)) {
            $suffix++;
            $handle = $base.'-'.$suffix;
        }

        return $handle;
    }

    private static function exists(string $handle, string $table, int $storeId, ?int $excludeId): bool
    {
        $query = DB::table($table)
            ->where('store_id', $storeId)
            ->where('handle', $handle);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
