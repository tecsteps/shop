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
    /**
     * Perform a full-text search for products within a store.
     *
     * @param  array{vendor?: string, min_price?: int, max_price?: int, collection_id?: int}  $filters
     */
    public function search(Store $store, string $query, array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        $sanitized = $this->sanitizeQuery($query);

        if ($sanitized === '') {
            return Product::query()
                ->where('store_id', $store->id)
                ->active()
                ->whereRaw('1 = 0')
                ->paginate($perPage);
        }

        $ftsQuery = $this->buildFtsExpression($sanitized);

        $productQuery = Product::query()
            ->withoutGlobalScopes()
            ->where('products.store_id', $store->id)
            ->where('products.status', ProductStatus::Active)
            ->whereIn('products.id', function ($sub) use ($ftsQuery) {
                $sub->select('rowid')
                    ->from('products_fts')
                    ->whereRaw('products_fts MATCH ?', [$ftsQuery]);
            })
            ->with(['variants.inventoryItem', 'media']);

        $this->applyFilters($productQuery, $filters);

        return $productQuery->paginate($perPage);
    }

    /**
     * Prefix-based autocomplete for search-as-you-type.
     */
    public function autocomplete(Store $store, string $prefix, int $limit = 8): Collection
    {
        $sanitized = $this->sanitizeQuery($prefix);

        if ($sanitized === '') {
            return collect();
        }

        // Use prefix matching: append * for prefix search
        $ftsQuery = '"'.$sanitized.'"*';

        return Product::query()
            ->withoutGlobalScopes()
            ->where('products.store_id', $store->id)
            ->where('products.status', ProductStatus::Active)
            ->whereIn('products.id', function ($sub) use ($ftsQuery) {
                $sub->select('rowid')
                    ->from('products_fts')
                    ->whereRaw('products_fts MATCH ?', [$ftsQuery]);
            })
            ->select(['products.id', 'products.title', 'products.handle'])
            ->limit($limit)
            ->get();
    }

    /**
     * Upsert a single product into the FTS5 index.
     *
     * Uses the same text transformation as the SQLite triggers to ensure
     * content consistency between trigger-synced and manually-synced entries.
     */
    public function syncProduct(Product $product): void
    {
        // Remove existing entry first, then insert fresh data.
        // This handles both new inserts and updates.
        $this->removeProduct($product->id);

        $description = $this->transformHtml($product->description_html);
        $tags = $this->transformTags($product->tags);

        DB::statement(
            'INSERT INTO products_fts(rowid, title, description, vendor, product_type, tags) VALUES (?, ?, ?, ?, ?, ?)',
            [
                $product->id,
                $product->title,
                $description,
                $product->vendor ?? '',
                $product->product_type ?? '',
                $tags,
            ]
        );
    }

    /**
     * Remove a product from the FTS5 index.
     */
    public function removeProduct(int $productId): void
    {
        // For external content tables, we need to supply the original values
        // when deleting. Query the content table to get the current values.
        $product = DB::table('products')->where('id', $productId)->first();

        if (! $product) {
            return;
        }

        $description = $this->transformHtml($product->description_html);
        $tags = $this->transformTagsRaw($product->tags);

        DB::statement(
            'INSERT INTO products_fts(products_fts, rowid, title, description, vendor, product_type, tags) VALUES (\'delete\', ?, ?, ?, ?, ?, ?)',
            [
                $productId,
                $product->title,
                $description,
                $product->vendor ?? '',
                $product->product_type ?? '',
                $tags,
            ]
        );
    }

    /**
     * Rebuild the entire FTS5 index for a store.
     *
     * Deletes all existing FTS entries and re-inserts products for the given store.
     * Since the FTS column name "description" differs from the table column
     * "description_html", the built-in rebuild command cannot be used. Instead,
     * we manually remove and re-insert each product.
     */
    public function reindex(Store $store): void
    {
        $products = DB::table('products')
            ->where('store_id', $store->id)
            ->get();

        foreach ($products as $product) {
            // Attempt to remove existing entry; ignore errors for missing entries
            try {
                $description = $this->transformHtml($product->description_html);
                $tags = $this->transformTagsRaw($product->tags);

                DB::statement(
                    'INSERT INTO products_fts(products_fts, rowid, title, description, vendor, product_type, tags) VALUES (\'delete\', ?, ?, ?, ?, ?, ?)',
                    [
                        $product->id,
                        $product->title,
                        $description,
                        $product->vendor ?? '',
                        $product->product_type ?? '',
                        $tags,
                    ]
                );
            } catch (\Throwable) {
                // Entry may not exist in FTS index
            }
        }

        // Re-insert all products for this store
        foreach ($products as $product) {
            $description = $this->transformHtml($product->description_html);
            $tags = $this->transformTagsRaw($product->tags);

            DB::statement(
                'INSERT INTO products_fts(rowid, title, description, vendor, product_type, tags) VALUES (?, ?, ?, ?, ?, ?)',
                [
                    $product->id,
                    $product->title,
                    $description,
                    $product->vendor ?? '',
                    $product->product_type ?? '',
                    $tags,
                ]
            );
        }
    }

    /**
     * Strip FTS5 special characters from user input to prevent query syntax errors.
     */
    private function sanitizeQuery(string $query): string
    {
        // Remove FTS5 special operators and extra whitespace
        $sanitized = preg_replace('/["\*\(\)\{\}\[\]\^~:]/u', ' ', $query);
        $sanitized = preg_replace('/\s+/', ' ', $sanitized);

        return trim($sanitized);
    }

    /**
     * Build an FTS5 match expression from sanitized search terms.
     * Each word is matched individually, allowing partial coverage across fields.
     */
    private function buildFtsExpression(string $query): string
    {
        $words = array_filter(explode(' ', $query));

        if (count($words) === 0) {
            return '""';
        }

        // Quote each individual word to prevent FTS5 syntax issues,
        // then combine with implicit AND. Append * for prefix matching.
        $terms = array_map(fn (string $word): string => '"'.$word.'"*', $words);

        return implode(' ', $terms);
    }

    /**
     * Transform HTML to plain text, matching the SQLite trigger transformation.
     *
     * The trigger uses: REPLACE(REPLACE(description_html, '<', ' '), '>', ' ')
     */
    private function transformHtml(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        return str_replace(['<', '>'], [' ', ' '], $html);
    }

    /**
     * Transform tags from a PHP array to a raw string.
     *
     * @param  array<int, string>|string|null  $tags
     */
    private function transformTags(array|string|null $tags): string
    {
        if (is_array($tags)) {
            return json_encode($tags) ?: '[]';
        }

        return $tags ?? '[]';
    }

    /**
     * Transform tags from a raw database string for FTS deletion.
     *
     * The trigger stores COALESCE(tags, '[]'), so the FTS entry
     * contains the raw JSON string from the database.
     */
    private function transformTagsRaw(?string $tags): string
    {
        return $tags ?? '[]';
    }

    /**
     * Apply optional filters to a product query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Product>  $query
     * @param  array{vendor?: string, min_price?: int, max_price?: int, collection_id?: int}  $filters
     */
    private function applyFilters(\Illuminate\Database\Eloquent\Builder $query, array $filters): void
    {
        if (! empty($filters['vendor'])) {
            $query->where('products.vendor', $filters['vendor']);
        }

        if (isset($filters['min_price'])) {
            $query->whereHas('variants', function ($q) use ($filters) {
                $q->where('price_amount', '>=', $filters['min_price']);
            });
        }

        if (isset($filters['max_price'])) {
            $query->whereHas('variants', function ($q) use ($filters) {
                $q->where('price_amount', '<=', $filters['max_price']);
            });
        }

        if (! empty($filters['collection_id'])) {
            $query->whereHas('collections', function ($q) use ($filters) {
                $q->where('collections.id', $filters['collection_id']);
            });
        }
    }
}
