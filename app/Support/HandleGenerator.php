<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HandleGenerator
{
    public function generate(string $title, string $table, int $storeId, ?int $excludeId = null): string
    {
        $base = Str::slug($title) ?: 'item';
        $handle = $base;
        $suffix = 2;

        while (DB::table($table)->where('store_id', $storeId)->where('handle', $handle)->when($excludeId !== null, fn ($query) => $query->where('id', '<>', $excludeId))->exists()) {
            $handle = $base.'-'.$suffix++;
        }

        return $handle;
    }
}
