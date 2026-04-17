<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HandleGenerator
{
    public function generate(string $title, string $table, int $storeId, ?int $excludeId = null): string
    {
        $base = Str::slug($title);

        if (empty($base)) {
            $base = 'item';
        }

        $handle = $base;
        $suffix = 0;

        while ($this->exists($handle, $table, $storeId, $excludeId)) {
            $suffix++;
            $handle = "{$base}-{$suffix}";
        }

        return $handle;
    }

    private function exists(string $handle, string $table, int $storeId, ?int $excludeId): bool
    {
        $query = DB::table($table)
            ->where('handle', $handle)
            ->where('store_id', $storeId);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
