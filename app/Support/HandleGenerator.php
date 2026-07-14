<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class HandleGenerator
{
    public function generate(string $title, string $table, int $storeId, ?int $excludeId = null): string
    {
        if (! in_array($table, ['products', 'collections', 'pages'], true)) {
            throw new InvalidArgumentException('Unsupported handle table.');
        }

        $base = Str::slug($title) ?: 'item';
        $candidate = $base;
        $suffix = 0;

        do {
            $query = DB::table($table)
                ->where('store_id', $storeId)
                ->where('handle', $candidate);

            if ($excludeId !== null) {
                $query->where('id', '!=', $excludeId);
            }

            if (! $query->exists()) {
                return $candidate;
            }

            $candidate = $base.'-'.(++$suffix);
        } while (true);
    }
}
