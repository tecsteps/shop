<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\ProductStatus;
use App\Enums\ProductVariantStatus;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class SearchController extends StorefrontController
{
    public function index(Request $request): View
    {
        $storeId = $this->currentStoreId($request);
        $queryTerm = trim((string) $request->query('q', ''));

        $products = Product::query()
            ->where('store_id', $storeId)
            ->where('status', ProductStatus::Active->value)
            ->whereNotNull('published_at')
            ->when($queryTerm !== '', function (Builder $query) use ($queryTerm): void {
                $like = '%'.$queryTerm.'%';

                $query->where(function (Builder $scopedQuery) use ($like): void {
                    $scopedQuery->where('title', 'like', $like)
                        ->orWhere('handle', 'like', $like)
                        ->orWhere('vendor', 'like', $like)
                        ->orWhere('product_type', 'like', $like);
                });
            })
            ->with([
                'variants' => fn ($variantQuery) => $variantQuery
                    ->where('status', ProductVariantStatus::Active->value)
                    ->orderByDesc('is_default')
                    ->orderBy('position')
                    ->orderBy('price_amount'),
            ])
            ->orderByDesc('published_at')
            ->orderBy('title')
            ->paginate(16)
            ->withQueryString();

        return view('storefront.search.index', [
            'query' => $queryTerm,
            'products' => $products,
        ]);
    }
}
