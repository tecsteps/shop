<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HandleGenerator
{
    public static function generate(string $title, string $table, int $storeId, ?int $excludeId = null): string
    {
        $handle = Str::slug($title);

        $query = DB::table($table)
            ->where('store_id', $storeId)
            ->where('handle', $handle);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        if (! $query->exists()) {
            return $handle;
        }

        $suffix = 1;

        do {
            $candidate = $handle.'-'.$suffix;

            $query = DB::table($table)
                ->where('store_id', $storeId)
                ->where('handle', $candidate);

            if ($excludeId !== null) {
                $query->where('id', '!=', $excludeId);
            }

            if (! $query->exists()) {
                return $candidate;
            }

            $suffix++;
        } while (true);
    }
}
