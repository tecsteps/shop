<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class HandleGenerator
{
    /**
     * Generate a unique URL-safe handle for a store-scoped model.
     *
     * @param  class-string<Model>  $modelClass
     */
    public static function unique(
        string $modelClass,
        int $storeId,
        string $source,
        ?int $ignoreId = null,
        string $column = 'handle'
    ): string {
        $base = Str::slug($source);

        if ($base === '') {
            $base = 'item';
        }

        $candidate = $base;
        $suffix = 1;

        while (self::exists($modelClass, $storeId, $column, $candidate, $ignoreId)) {
            $suffix++;
            $candidate = $base.'-'.$suffix;
        }

        return $candidate;
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    protected static function exists(
        string $modelClass,
        int $storeId,
        string $column,
        string $value,
        ?int $ignoreId
    ): bool {
        $query = $modelClass::query()
            ->withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->where($column, $value);

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }
}
