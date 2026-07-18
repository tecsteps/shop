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

        $handle = $base;
        $suffix = 2;

        while ($this->exists($table, $storeId, $handle, $excludeId)) {
            $handle = $base.'-'.$suffix;
            $suffix++;
        }

        return $handle;
    }

    private function exists(string $table, int $storeId, string $handle, ?int $excludeId): bool
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
