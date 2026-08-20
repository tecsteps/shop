<?php

namespace App\Services;

use App\Models\Product;
use App\Models\SearchQuery;
use App\Models\Store;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SearchService
{
    public function search(Store $store, string $query, array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        $products = Product::withoutGlobalScopes()
            ->published()
            ->where('store_id', $store->getKey())
            ->when(trim($query) !== '', function (Builder $builder) use ($query): void {
                $tokens = preg_split('/[^\pL\pN]+/u', trim($query), -1, PREG_SPLIT_NO_EMPTY);
                $match = collect($tokens ?: [])->map(fn (string $token): string => '"'.str_replace('"', '""', $token).'*"')->implode(' ');

                if ($match !== '' && Schema::hasTable('products_fts')) {
                    $builder->whereIn('id', DB::table('products_fts')->select('product_id')->whereColumn('products_fts.store_id', 'products.store_id')->whereRaw('products_fts MATCH ?', [$match]));
                }
            })
            ->when($filters['vendor'] ?? null, fn (Builder $builder, string $vendor): Builder => $builder->where('vendor', $vendor))
            ->when(isset($filters['min_price']), fn (Builder $builder): Builder => $builder->whereHas('variants', fn (Builder $variants): Builder => $variants->where('price_amount', '>=', (int) $filters['min_price'])))
            ->when(isset($filters['max_price']), fn (Builder $builder): Builder => $builder->whereHas('variants', fn (Builder $variants): Builder => $variants->where('price_amount', '<=', (int) $filters['max_price'])))
            ->with(['variants.inventory', 'media'])
            ->latest('published_at')
            ->paginate($perPage);

        if (Schema::hasTable('search_queries')) {
            SearchQuery::withoutGlobalScopes()->create(['store_id' => $store->getKey(), 'query' => $query, 'results_count' => $products->total(), 'customer_id' => auth('customer')->id()]);
        }

        return $products;
    }

    public function autocomplete(Store $store, string $prefix, int $limit = 8): Collection
    {
        if (mb_strlen(trim($prefix)) < 2) {
            return collect();
        }

        return Product::withoutGlobalScopes()
            ->published()
            ->where('store_id', $store->getKey())
            ->where('title', 'like', trim($prefix).'%')
            ->orderBy('title')
            ->limit($limit)
            ->get(['id', 'title', 'handle']);
    }

    public function syncProduct(Product $product): void
    {
        if (! Schema::hasTable('products_fts')) {
            return;
        }

        $this->removeProduct($product->getKey());
        DB::table('products_fts')->insert([
            'product_id' => $product->getKey(),
            'store_id' => $product->store_id,
            'title' => $product->title,
            'description' => $product->description,
            'vendor' => $product->vendor,
            'product_type' => $product->product_type,
            'tags' => is_array($product->tags) ? implode(' ', $product->tags) : (string) $product->tags,
        ]);
    }

    public function removeProduct(int $productId): void
    {
        if (Schema::hasTable('products_fts')) {
            DB::table('products_fts')->where('product_id', $productId)->delete();
        }
    }
}
