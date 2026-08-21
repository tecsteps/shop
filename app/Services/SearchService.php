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
    public function search(Store $store, string $query, array $filters = [], int $perPage = 24, int $page = 1, string $sort = 'relevance'): LengthAwarePaginator
    {
        $minimumPrice = $filters['price_min'] ?? $filters['min_price'] ?? null;
        $maximumPrice = $filters['price_max'] ?? $filters['max_price'] ?? null;
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
            ->when(isset($filters['collection_id']), fn (Builder $builder): Builder => $builder->whereHas('collections', fn (Builder $collections): Builder => $collections->whereKey((int) $filters['collection_id'])))
            ->when($minimumPrice !== null, fn (Builder $builder): Builder => $builder->whereHas('variants', fn (Builder $variants): Builder => $variants->where('price_amount', '>=', (int) $minimumPrice)))
            ->when($maximumPrice !== null, fn (Builder $builder): Builder => $builder->whereHas('variants', fn (Builder $variants): Builder => $variants->where('price_amount', '<=', (int) $maximumPrice)))
            ->when($filters['in_stock'] ?? false, fn (Builder $builder): Builder => $builder->whereHas('variants', fn (Builder $variants): Builder => $variants->whereHas('inventory', fn (Builder $inventory): Builder => $inventory->whereColumn('quantity_on_hand', '>', 'quantity_reserved')->orWhere('policy', 'continue'))))
            ->when($filters['tags'] ?? [], fn (Builder $builder, array $tags): Builder => $builder->where(function (Builder $products) use ($tags): void {
                foreach ($tags as $tag) {
                    $products->whereJsonContains('tags', $tag);
                }
            }))
            ->with(['variants.inventory', 'media'])
            ->when($sort === 'price_asc', fn (Builder $builder): Builder => $builder->withMin('variants', 'price_amount')->orderBy('variants_min_price_amount'))
            ->when($sort === 'price_desc', fn (Builder $builder): Builder => $builder->withMin('variants', 'price_amount')->orderByDesc('variants_min_price_amount'))
            ->when($sort === 'best_selling', fn (Builder $builder): Builder => $builder->orderByDesc('sales_count'))
            ->when(! in_array($sort, ['price_asc', 'price_desc', 'best_selling'], true), fn (Builder $builder): Builder => $builder->latest('published_at'))
            ->paginate($perPage, ['*'], 'page', $page);

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
            ->where('title', 'like', '%'.trim($prefix).'%')
            ->orderBy('title')
            ->limit($limit)
            ->with(['media', 'variants'])
            ->get();
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
