<?php

namespace App\Http\Controllers;

use App\Enums\ProductStatus;
use App\Models\Collection;
use App\Models\Page;
use App\Models\Product;
use App\Services\Shop\SearchService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StorefrontController extends Controller
{
    public function home(): View
    {
        $products = Product::query()
            ->with('defaultVariant', 'media')
            ->where('status', ProductStatus::Active)
            ->latest()
            ->limit(8)
            ->get();

        $collections = Collection::query()
            ->where('is_published', true)
            ->orderBy('title')
            ->get();

        return view('storefront.home', compact('products', 'collections'));
    }

    public function collections(): View
    {
        $collections = Collection::query()
            ->withCount('products')
            ->where('is_published', true)
            ->orderBy('title')
            ->get();

        return view('storefront.collections.index', compact('collections'));
    }

    public function collection(string $handle): View
    {
        $collection = Collection::query()
            ->where('handle', $handle)
            ->where('is_published', true)
            ->firstOrFail();

        $products = $collection->products()
            ->with('defaultVariant', 'media')
            ->where('status', ProductStatus::Active)
            ->paginate(12);

        return view('storefront.collections.show', compact('collection', 'products'));
    }

    public function product(string $handle): View
    {
        $product = Product::query()
            ->with('variants.optionValues.option', 'variants.inventoryItem', 'media')
            ->where('handle', $handle)
            ->where('status', ProductStatus::Active)
            ->firstOrFail();

        return view('storefront.products.show', compact('product'));
    }

    public function search(Request $request, SearchService $search): View
    {
        $query = trim((string) $request->query('q', ''));
        $products = $query === ''
            ? collect()
            : $search->products($query);

        return view('storefront.search', compact('query', 'products'));
    }

    public function page(string $handle): View
    {
        $page = Page::query()
            ->where('handle', $handle)
            ->where('is_published', true)
            ->firstOrFail();

        return view('storefront.page', compact('page'));
    }
}
