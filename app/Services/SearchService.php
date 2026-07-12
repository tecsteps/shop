<?php

namespace App\Services;

use App\Models\Product;
use App\Models\SearchQuery;
use App\Models\Store;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

final class SearchService
{
    /** @param array<string, mixed> $filters */
    public function search(Store $store, string $query, array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        $query = trim($query);
        $page = max(1, LengthAwarePaginator::resolveCurrentPage());
        $ids = $this->matchingIds($store, $query);
        $products = Product::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('status', 'active')
            ->whereNotNull('published_at')
            ->when($ids !== null, fn ($builder) => $builder->whereIn('id', $ids))
            ->when($ids === null && $query !== '', fn ($builder) => $builder->where(function ($nested) use ($query): void {
                $nested->where('title', 'like', "%{$query}%")
                    ->orWhere('description_html', 'like', "%{$query}%")
                    ->orWhere('vendor', 'like', "%{$query}%");
            }))
            ->when(isset($filters['vendor']), fn ($builder) => $builder->where('vendor', $filters['vendor']))
            ->with(['variants', 'media'])
            ->get();

        if ($ids !== null) {
            $order = array_flip($ids);
            $products = $products->sortBy(fn (Product $product): int => $order[$product->id] ?? PHP_INT_MAX)->values();
        }
        $total = $products->count();
        $items = $products->forPage($page, $perPage)->values();

        SearchQuery::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'query' => $query,
            'filters_json' => $filters,
            'results_count' => $total,
        ]);

        return new LengthAwarePaginator($items, $total, $perPage, $page, ['path' => request()->url(), 'query' => request()->query()]);
    }

    /** @return Collection<int, Product> */
    public function autocomplete(Store $store, string $prefix, int $limit = 8): Collection
    {
        $results = $this->search($store, $prefix, [], $limit);

        return collect($results->items());
    }

    public function syncProduct(Product $product): void
    {
        try {
            DB::table('products_fts')->where('product_id', $product->id)->delete();
            DB::table('products_fts')->insert([
                'store_id' => $product->store_id,
                'product_id' => $product->id,
                'title' => $product->title,
                'description' => strip_tags((string) $product->description_html),
                'vendor' => (string) $product->vendor,
                'product_type' => (string) $product->product_type,
                'tags' => implode(' ', (array) $product->tags),
            ]);
        } catch (Throwable) {
            // FTS5 can be unavailable in minimal SQLite builds; LIKE remains a safe fallback.
        }
    }

    public function removeProduct(int $productId): void
    {
        try {
            DB::table('products_fts')->where('product_id', $productId)->delete();
        } catch (Throwable) {
            // See syncProduct fallback.
        }
    }

    /** @return list<int>|null */
    private function matchingIds(Store $store, string $query): ?array
    {
        if ($query === '') {
            return null;
        }

        try {
            $tokens = preg_split('/\s+/', preg_replace('/[^\pL\pN\s-]+/u', ' ', $query) ?: '') ?: [];
            $match = implode(' ', array_map(fn (string $token): string => '"'.str_replace('"', '""', $token).'"*', array_filter($tokens)));
            if ($match === '') {
                return [];
            }

            $rows = DB::select("SELECT product_id FROM products_fts WHERE products_fts MATCH ? AND store_id = ? ORDER BY rank", [$match, $store->id]);

            return array_map(fn (object $row): int => (int) $row->product_id, $rows);
        } catch (Throwable) {
            return null;
        }
    }
}
