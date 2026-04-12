<?php

namespace App\Services;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SearchService
{
    public function syncProduct(Product $product): void
    {
        $this->removeProduct($product->id);

        $tags = $product->tags;
        $tagString = is_array($tags) ? implode(' ', $tags) : (string) ($tags ?? '');

        DB::statement(
            'INSERT INTO products_fts (rowid, title, description, vendor, product_type, tags) VALUES (?, ?, ?, ?, ?, ?)',
            [
                $product->id,
                (string) ($product->title ?? ''),
                strip_tags((string) ($product->description_html ?? '')),
                (string) ($product->vendor ?? ''),
                (string) ($product->product_type ?? ''),
                $tagString,
            ]
        );
    }

    public function removeProduct(int $productId): void
    {
        DB::statement('DELETE FROM products_fts WHERE rowid = ?', [$productId]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Product>
     */
    public function search(Store $store, string $query, array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        $ftsQuery = $this->buildFtsQuery($query);

        if ($ftsQuery === null) {
            return Product::query()->whereRaw('1 = 0')->paginate($perPage);
        }

        $ids = DB::table('products_fts')
            ->whereRaw('products_fts MATCH ?', [$ftsQuery])
            ->pluck('rowid')
            ->all();

        if ($ids === []) {
            return Product::query()->whereRaw('1 = 0')->paginate($perPage);
        }

        return Product::query()
            ->whereIn('id', $ids)
            ->where('store_id', $store->id)
            ->where('status', ProductStatus::Active->value)
            ->orderBy('title')
            ->paginate($perPage);
    }

    /**
     * @return Collection<int, Product>
     */
    public function autocomplete(Store $store, string $prefix, int $limit = 5): Collection
    {
        if (mb_strlen(trim($prefix)) < 2) {
            return collect();
        }

        $ftsQuery = $this->buildFtsQuery($prefix);

        if ($ftsQuery === null) {
            return collect();
        }

        $ids = DB::table('products_fts')
            ->whereRaw('products_fts MATCH ?', [$ftsQuery])
            ->limit($limit * 3)
            ->pluck('rowid')
            ->all();

        if ($ids === []) {
            return collect();
        }

        return Product::query()
            ->whereIn('id', $ids)
            ->where('store_id', $store->id)
            ->where('status', ProductStatus::Active->value)
            ->limit($limit)
            ->get();
    }

    private function buildFtsQuery(string $query): ?string
    {
        $sanitized = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $query) ?? '';
        $tokens = preg_split('/\s+/', trim($sanitized)) ?: [];
        $tokens = array_values(array_filter($tokens, fn (string $token): bool => $token !== ''));

        if ($tokens === []) {
            return null;
        }

        return implode(' ', array_map(fn (string $token): string => $token.'*', $tokens));
    }
}
