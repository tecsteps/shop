<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class HandleGenerator
{
    /** @var list<string> */
    private const ALLOWED_TABLES = ['products', 'collections', 'pages'];

    public function generate(string $title, string $table, int $storeId, ?int $excludeId = null): string
    {
        if (! in_array($table, self::ALLOWED_TABLES, true)) {
            throw new InvalidArgumentException("Handles cannot be generated for the [{$table}] table.");
        }

        $baseHandle = Str::slug($title);
        $baseHandle = $baseHandle !== '' ? $baseHandle : 'item';
        $handle = $baseHandle;
        $suffix = 1;

        while ($this->exists($table, $storeId, $handle, $excludeId)) {
            $handle = $baseHandle.'-'.$suffix;
            $suffix++;
        }

        return $handle;
    }

    private function exists(string $table, int $storeId, string $handle, ?int $excludeId): bool
    {
        return DB::table($table)
            ->where('store_id', $storeId)
            ->where('handle', $handle)
            ->when($excludeId !== null, fn ($query) => $query->where('id', '!=', $excludeId))
            ->exists();
    }
}
