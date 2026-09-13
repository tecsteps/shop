<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\StoreMediaRequest;
use App\Http\Requests\Api\Admin\UpdateMediaRequest;
use App\Http\Resources\Api\Admin\ProductMediaResource;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Services\MediaService;
use App\Tenancy\CurrentStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ProductMediaController extends Controller
{
    public function __construct(
        protected MediaService $media,
        protected CurrentStore $currentStore,
    ) {}

    public function store(StoreMediaRequest $request, int $storeId, int $productId): JsonResponse
    {
        $product = $this->findProduct($productId);

        Gate::authorize('update', $product);

        $validated = $request->validated();

        $media = $this->media->upload($product, $validated['file'], $validated['alt_text'] ?? null);

        return (new ProductMediaResource($media))->response()->setStatusCode(201);
    }

    public function update(UpdateMediaRequest $request, int $storeId, int $productId, int $mediaId): JsonResponse
    {
        $product = $this->findProduct($productId);

        Gate::authorize('update', $product);

        $media = $this->findMedia($product, $mediaId);
        $updated = $this->media->updateMedia($media, $request->validated());

        return (new ProductMediaResource($updated))->response();
    }

    public function destroy(int $storeId, int $productId, int $mediaId): JsonResponse
    {
        $product = $this->findProduct($productId);

        Gate::authorize('update', $product);

        $this->media->deleteMedia($this->findMedia($product, $mediaId));

        return response()->json(['message' => 'Media deleted']);
    }

    protected function findProduct(int $productId): Product
    {
        return Product::query()
            ->where('store_id', $this->currentStore->id())
            ->whereKey($productId)
            ->firstOrFail();
    }

    protected function findMedia(Product $product, int $mediaId): ProductMedia
    {
        return $product->productMedia()->whereKey($mediaId)->firstOrFail();
    }
}
