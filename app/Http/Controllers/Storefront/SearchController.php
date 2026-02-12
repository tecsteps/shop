<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\CollectionStatus;
use App\Enums\PageStatus;
use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Models\Collection;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class SearchController extends StorefrontController
{
    public function index(Request $request)
    {
        $query = trim((string) $request->query('q', ''));
        $sort = (string) $request->query('sort', 'relevance');

        $productsQuery = Product::query()
            ->where('status', ProductStatus::Active)
            ->whereHas('variants', fn ($builder) => $builder->where('status', VariantStatus::Active))
            ->withMin('variants as min_price_amount', 'price_amount')
            ->with([
                'media' => fn ($builder) => $builder->orderBy('position'),
                'defaultVariant.inventoryItem',
            ]);

        if ($query !== '') {
            $productsQuery->where(function (Builder $builder) use ($query): void {
                $builder->where('title', 'like', "%{$query}%")
                    ->orWhere('vendor', 'like', "%{$query}%")
                    ->orWhere('product_type', 'like', "%{$query}%")
                    ->orWhere('description_html', 'like', "%{$query}%");
            });
        }

        match ($sort) {
            'price_low' => $productsQuery->orderBy('min_price_amount')->orderBy('title'),
            'price_high' => $productsQuery->orderByDesc('min_price_amount')->orderBy('title'),
            'newest' => $productsQuery->orderByDesc('published_at')->orderByDesc('id'),
            default => $productsQuery->orderBy('title'),
        };

        $products = $productsQuery
            ->paginate(12)
            ->withQueryString();

        $collections = collect();
        $pages = collect();

        if ($query !== '') {
            $collections = Collection::query()
                ->where('status', CollectionStatus::Active)
                ->where('title', 'like', "%{$query}%")
                ->limit(5)
                ->get();

            $pages = Page::query()
                ->where('status', PageStatus::Published)
                ->where('title', 'like', "%{$query}%")
                ->limit(5)
                ->get();
        }

        return view('storefront.search.index', $this->storefrontViewData($request, [
            'query' => $query,
            'sort' => $sort,
            'products' => $products,
            'collections' => $collections,
            'pages' => $pages,
            'title' => $query === '' ? 'Search' : "Search: {$query}",
            'metaDescription' => 'Search products, collections, and pages.',
        ]));
    }
}
