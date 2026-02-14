<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class HandleGenerator
{
    public function generate(string $title, string $table, int $storeId, ?int $excludeId = null): string
    {
        $baseHandle = Str::slug($title);

        if ($baseHandle === '') {
            $baseHandle = 'item';
        }

        $candidate = $baseHandle;
        $suffix = 0;

        while ($this->handleExists($candidate, $table, $storeId, $excludeId)) {
            $suffix++;
            $candidate = sprintf('%s-%d', $baseHandle, $suffix);
        }

        return $candidate;
    }

    private function handleExists(string $handle, string $table, int $storeId, ?int $excludeId): bool
    {
        $query = DB::table($table)
            ->where('store_id', '=', $storeId)
            ->where('handle', '=', $handle);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
