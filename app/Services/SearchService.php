<?php

namespace App\Services;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SearchService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function search(Store $store, string $query, array $filters = [], int $perPage = 12, int $page = 1): LengthAwarePaginator
    {
        $trimmed = trim($query);

        if ($trimmed === '' || ! $this->hasFts()) {
            $empty = new Paginator([], 0, $perPage, $page);
            $this->logQuery($store, $trimmed, $filters, 0);

            return $empty;
        }

        $match = $this->buildMatchExpression($trimmed);

        $ids = DB::table('products_fts')
            ->where('store_id', $store->id)
            ->whereRaw('products_fts MATCH ?', [$match])
            ->orderByRaw('rank')
            ->pluck('product_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $productsQuery = Product::query()
            ->where('store_id', $store->id)
            ->where('status', ProductStatus::Active)
            ->whereIn('id', $ids);

        if (! empty($filters['vendor'])) {
            $productsQuery->where('vendor', $filters['vendor']);
        }

        if (! empty($filters['product_type'])) {
            $productsQuery->where('product_type', $filters['product_type']);
        }

        $total = (clone $productsQuery)->count();

        if (! empty($ids)) {
            $productsQuery->orderByRaw('CASE id '.$this->caseOrder($ids).' END');
        }

        $items = $productsQuery
            ->forPage($page, $perPage)
            ->get();

        $paginator = new Paginator($items, $total, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => 'page',
        ]);

        $this->logQuery($store, $trimmed, $filters, $total);

        return $paginator;
    }

    /**
     * @return Collection<int, object>
     */
    public function autocomplete(Store $store, string $prefix, int $limit = 8): Collection
    {
        $trimmed = trim($prefix);

        if (mb_strlen($trimmed) < 2 || ! $this->hasFts()) {
            return new Collection;
        }

        $match = $this->buildMatchExpression($trimmed, prefix: true);

        $rows = DB::table('products_fts')
            ->where('store_id', $store->id)
            ->whereRaw('products_fts MATCH ?', [$match])
            ->orderByRaw('rank')
            ->limit($limit)
            ->pluck('product_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        if (empty($rows)) {
            return new Collection;
        }

        return Product::query()
            ->where('store_id', $store->id)
            ->where('status', ProductStatus::Active)
            ->whereIn('id', $rows)
            ->orderByRaw('CASE id '.$this->caseOrder($rows).' END')
            ->get();
    }

    public function syncProduct(Product $product): void
    {
        if (! $this->hasFts()) {
            return;
        }

        DB::table('products_fts')->where('product_id', $product->id)->delete();

        if ($product->status !== ProductStatus::Active) {
            return;
        }

        DB::table('products_fts')->insert([
            'product_id' => $product->id,
            'store_id' => $product->store_id,
            'title' => $product->title ?? '',
            'description' => $this->stripTags($product->description_html),
            'vendor' => $product->vendor ?? '',
            'product_type' => $product->product_type ?? '',
            'tags' => $this->tagsToText($product->tags),
        ]);
    }

    public function removeProduct(int $productId): void
    {
        if (! $this->hasFts()) {
            return;
        }

        DB::table('products_fts')->where('product_id', $productId)->delete();
    }

    protected function hasFts(): bool
    {
        return Schema::hasTable('products_fts');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function logQuery(Store $store, string $query, array $filters, int $count): void
    {
        if (! Schema::hasTable('search_queries') || $query === '') {
            return;
        }

        DB::table('search_queries')->insert([
            'store_id' => $store->id,
            'query' => $query,
            'filters_json' => empty($filters) ? null : json_encode($filters),
            'results_count' => $count,
            'created_at' => now(),
        ]);
    }

    protected function buildMatchExpression(string $query, bool $prefix = false): string
    {
        $tokens = preg_split('/[^\p{L}\p{N}]+/u', $query) ?: [];
        $tokens = array_filter($tokens, fn ($t): bool => $t !== '');

        $sanitized = array_map(function (string $token) use ($prefix): string {
            $quoted = '"'.$token.'"';

            return $prefix ? $quoted.'*' : $quoted;
        }, $tokens);

        if (empty($sanitized)) {
            return '""';
        }

        return implode(' ', $sanitized);
    }

    /**
     * @param  array<int, int>  $ids
     */
    protected function caseOrder(array $ids): string
    {
        $clauses = [];
        foreach ($ids as $index => $id) {
            $clauses[] = 'WHEN '.(int) $id.' THEN '.$index;
        }

        return implode(' ', $clauses);
    }

    protected function stripTags(?string $html): string
    {
        if ($html === null) {
            return '';
        }

        return trim(preg_replace('/\s+/', ' ', strip_tags($html)) ?? '');
    }

    /**
     * @param  mixed  $tags
     */
    protected function tagsToText($tags): string
    {
        if (is_array($tags)) {
            return implode(' ', array_map('strval', $tags));
        }

        if (is_string($tags)) {
            $decoded = json_decode($tags, true);
            if (is_array($decoded)) {
                return implode(' ', array_map('strval', $decoded));
            }

            return $tags;
        }

        return '';
    }
}
