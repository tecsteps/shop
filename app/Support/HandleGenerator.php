<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class HandleGenerator
{
    /**
     * @param  'products'|'collections'|'pages'  $table
     */
    public function generate(string $title, string $table, int $storeId, ?int $excludeId = null): string
    {
        if (! in_array($table, ['products', 'collections', 'pages'], true)) {
            throw new InvalidArgumentException('Handles may only be generated for supported store content.');
        }

        $base = Str::slug($title) ?: 'item';
        $candidate = $base;
        $suffix = 1;

        while (DB::table($table)
            ->where('store_id', $storeId)
            ->where('handle', $candidate)
            ->when($excludeId, fn ($query) => $query->where('id', '!=', $excludeId))
            ->exists()) {
            $candidate = $base.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }
}
