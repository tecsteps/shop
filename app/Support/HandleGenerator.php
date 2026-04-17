<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HandleGenerator
{
    /**
     * Generate a unique URL-friendly slug scoped to a store.
     */
    public function generate(string $title, string $table, int $storeId, ?int $excludeId = null): string
    {
        $base = Str::slug($title);

        if ($base === '') {
            $base = 'item';
        }

        $handle = $base;
        $suffix = 1;

        while ($this->handleExists($handle, $table, $storeId, $excludeId)) {
            $handle = $base.'-'.$suffix;
            $suffix++;
        }

        return $handle;
    }

    private function handleExists(string $handle, string $table, int $storeId, ?int $excludeId): bool
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
