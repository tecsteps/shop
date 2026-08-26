<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HandleGenerator
{
    public function generate(string $title, string $table, int $storeId, ?int $excludeId = null): string
    {
        $handle = Str::slug($title);

        if ($handle === '') {
            $handle = 'item';
        }

        $base = $handle;
        $suffix = 0;

        while ($this->exists($table, $handle, $storeId, $excludeId)) {
            $suffix++;
            $handle = $base.'-'.$suffix;
        }

        return $handle;
    }

    private function exists(string $table, string $handle, int $storeId, ?int $excludeId): bool
    {
        return DB::table($table)
            ->where('store_id', $storeId)
            ->where('handle', $handle)
            ->when($excludeId, fn ($query) => $query->where('id', '!=', $excludeId))
            ->exists();
    }
}
