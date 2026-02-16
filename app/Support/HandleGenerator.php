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

        $handle = $base;
        $counter = 1;

        while (static::handleExists($handle, $table, $storeId, $excludeId)) {
            $handle = $base.'-'.$counter;
            $counter++;
        }

        return $handle;
    }

    private static function handleExists(string $handle, string $table, int $storeId, ?int $excludeId): bool
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
