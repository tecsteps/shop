<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HandleGenerator
{
    /**
     * Generate a unique handle (slug) for a given title within a store-scoped table.
     */
    public function generate(string $title, string $table, int $storeId, ?int $excludeId = null): string
    {
        $baseHandle = Str::slug($title);

        if ($baseHandle === '') {
            $baseHandle = 'item';
        }

        $query = DB::table($table)
            ->where('store_id', $storeId)
            ->where('handle', $baseHandle);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        if (! $query->exists()) {
            return $baseHandle;
        }

        $suffix = 1;

        while (true) {
            $candidate = $baseHandle.'-'.$suffix;

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
        }
    }
}
