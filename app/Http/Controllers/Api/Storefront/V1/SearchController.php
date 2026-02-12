<?php

namespace App\Http\Controllers\Api\Storefront\V1;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function suggest(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:1', 'max:100'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:10'],
        ]);

        $query = $request->string('q');
        $limit = $request->integer('limit', 5);
        $store = app('current_store');

        $products = Product::query()
            ->where('store_id', $store->id)
            ->where('status', ProductStatus::Active)
            ->where('title', 'LIKE', "%{$query}%")
            ->with(['variants' => fn ($q) => $q->orderBy('position')->limit(1), 'media' => fn ($q) => $q->orderBy('position')->limit(1)])
            ->limit($limit)
            ->get();

        $suggestions = $products->map(fn (Product $product) => [
            'type' => 'product',
            'title' => $product->title,
            'handle' => $product->handle,
            'image_url' => $product->media->first()?->url,
            'price_amount' => $product->variants->first()?->price_amount,
            'currency' => $store->default_currency,
        ]);

        return response()->json([
            'query' => (string) $query,
            'suggestions' => $suggestions->toArray(),
        ]);
    }
}
