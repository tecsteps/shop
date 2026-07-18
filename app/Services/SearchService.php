<?php

namespace App\Services;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\SearchQuery;
use App\Models\Store;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SearchService
{
    public function search(Store $store, string $query, array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        $term = trim($query);

        $builder = Product::query()
            ->where('store_id', $store->id)
            ->where('status', ProductStatus::Active);

        if ($term !== '') {
            $ids = collect(DB::select(
                'select product_id from products_fts where store_id = ? and products_fts match ? order by rank limit 500',
                [$store->id, $this->toMatchQuery($term)]
            ))->pluck('product_id');

            if ($ids->isEmpty()) {
                $builder->where(function ($q) use ($term): void {
                    $like = '%'.$term.'%';
                    $q->where('title', 'like', $like)
                        ->orWhere('vendor', 'like', $like)
                        ->orWhere('product_type', 'like', $like);
                });
            } else {
                $builder->whereIn('id', $ids);
            }
        }

        if (! empty($filters['vendor'])) {
            $builder->where('vendor', $filters['vendor']);
        }

        $results = $builder->paginate($perPage);

        SearchQuery::query()->create([
            'store_id' => $store->id,
            'query' => $term,
            'filters_json' => $filters,
            'results_count' => $results->total(),
            'created_at' => now(),
        ]);

        return $results;
    }

    public function autocomplete(Store $store, string $prefix, int $limit = 8): Collection
    {
        return Product::query()
            ->where('store_id', $store->id)
            ->where('status', ProductStatus::Active)
            ->where('title', 'like', $prefix.'%')
            ->limit($limit)
            ->get(['id', 'title', 'handle']);
    }

    public function syncProduct(Product $product): void
    {
        DB::table('products_fts')->where('product_id', $product->id)->delete();

        if ($product->status !== ProductStatus::Active) {
            return;
        }

        DB::table('products_fts')->insert([
            'product_id' => $product->id,
            'store_id' => $product->store_id,
            'title' => $product->title,
            'description' => strip_tags((string) $product->description_html),
            'vendor' => (string) $product->vendor,
            'product_type' => (string) $product->product_type,
            'tags' => implode(' ', $product->tags ?? []),
        ]);
    }

    public function removeProduct(int $productId): void
    {
        DB::table('products_fts')->where('product_id', $productId)->delete();
    }

    private function toMatchQuery(string $term): string
    {
        return collect(preg_split('/\s+/', $term))
            ->filter()
            ->map(fn (string $part) => '"'.str_replace('"', '""', $part).'"*')
            ->implode(' AND ');
    }
}
