<?php

namespace App\Services\Shop;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\SearchQuery;
use Illuminate\Database\Eloquent\Collection;

class SearchService
{
    /**
     * @return Collection<int, Product>
     */
    public function products(string $query): Collection
    {
        $clean = trim($query);

        $products = Product::query()
            ->with('defaultVariant', 'media')
            ->where('status', ProductStatus::Active)
            ->where(function ($builder) use ($clean): void {
                $builder->where('title', 'like', "%{$clean}%")
                    ->orWhere('description_html', 'like', "%{$clean}%")
                    ->orWhere('vendor', 'like', "%{$clean}%")
                    ->orWhere('product_type', 'like', "%{$clean}%");
            })
            ->orderBy('title')
            ->get();

        SearchQuery::query()->create([
            'query' => $clean,
            'results_count' => $products->count(),
        ]);

        return $products;
    }
}

