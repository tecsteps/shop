<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HandleGenerator
{
    public function generate(string $title, string $table, int $storeId, ?int $excludeId = null): string
    {
        $baseHandle = Str::slug($title);

        if (! $baseHandle) {
            $baseHandle = 'item';
        }

        $handle = $baseHandle;
        $suffix = 0;

        while ($this->handleExists($handle, $table, $storeId, $excludeId)) {
            $suffix++;
            $handle = $baseHandle.'-'.$suffix;
        }

        return $handle;
    }

    protected function handleExists(string $handle, string $table, int $storeId, ?int $excludeId): bool
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
