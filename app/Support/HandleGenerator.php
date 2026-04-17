<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HandleGenerator
{
    public static function generate(string $title, string $table, int $storeId, ?int $excludeId = null): string
    {
        $base = Str::slug($title);

        if ($base === '') {
            $base = 'item';
        }

        $candidate = $base;
        $suffix = 1;

        while (self::exists($table, $storeId, $candidate, $excludeId)) {
            $suffix++;
            $candidate = $base.'-'.$suffix;
        }

        return $candidate;
    }

    private static function exists(string $table, int $storeId, string $handle, ?int $excludeId): bool
    {
        $query = DB::table($table)
            ->where('store_id', $storeId)
            ->where('handle', $handle);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
