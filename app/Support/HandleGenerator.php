<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Generates URL-friendly, store-unique handles (slugs) for catalog resources.
 *
 * The handle is derived from a title by slugifying it (lowercase, hyphenated,
 * special characters stripped). Uniqueness is enforced per store against the
 * target table's `(store_id, handle)` index; on collision an incrementing
 * numeric suffix is appended (`my-product`, `my-product-1`, `my-product-2`).
 *
 * Used for products, collections, and pages.
 */
class HandleGenerator
{
    /**
     * Generate a store-unique handle for the given title.
     *
     * @param  string  $title  The human title to slugify.
     * @param  string  $table  The table whose `(store_id, handle)` must stay unique.
     * @param  int  $storeId  The owning store; uniqueness is scoped to it.
     * @param  int|null  $excludeId  A row id to ignore during the collision check
     *                               (the record currently being updated).
     */
    public function generate(string $title, string $table, int $storeId, ?int $excludeId = null): string
    {
        $base = Str::slug($title);

        if ($base === '') {
            $base = 'untitled';
        }

        $candidate = $base;
        $suffix = 0;

        while ($this->exists($candidate, $table, $storeId, $excludeId)) {
            $suffix++;
            $candidate = $base.'-'.$suffix;
        }

        return $candidate;
    }

    /**
     * Whether a handle already exists for the store (excluding one row id).
     */
    private function exists(string $handle, string $table, int $storeId, ?int $excludeId): bool
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
