<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HandleGenerator
{
    public function generate(string $title, string $table, int $storeId, ?int $excludeId = null): string
    {
        $base = Str::slug($title);

        if ($base === '') {
            $base = 'item';
        }

        $candidate = $base;
        $suffix = 1;

        while ($this->exists($table, $storeId, $candidate, $excludeId)) {
            $candidate = $base.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function exists(string $table, int $storeId, string $handle, ?int $excludeId): bool
    {
        return DB::table($table)
            ->where('store_id', $storeId)
            ->where('handle', $handle)
            ->when($excludeId, fn ($query) => $query->where('id', '!=', $excludeId))
            ->exists();
    }
}
