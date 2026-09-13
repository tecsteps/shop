<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Enums\ProductStatus;
use App\Exceptions\InvalidProductTransitionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\StoreProductRequest;
use App\Http\Requests\Api\Admin\UpdateProductRequest;
use App\Http\Resources\Api\Admin\ProductResource;
use App\Http\Resources\Api\Admin\ProductSummaryResource;
use App\Models\Product;
use App\Services\ProductService;
use App\Tenancy\CurrentStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $products,
        protected CurrentStore $currentStore,
    ) {}

    public function index(Request $request, int $storeId): JsonResponse
    {
        Gate::authorize('viewAny', Product::class);

        $validated = $request->validate([
            'status' => 'nullable|in:draft,active,archived',
            'query' => 'nullable|string|max:255',
            'collection_id' => 'nullable|integer',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'sort' => 'nullable|in:title_asc,title_desc,created_at_asc,created_at_desc,updated_at_desc',
        ]);

        $query = Product::query()
            ->where('store_id', $this->currentStore->id())
            ->withCount('productVariants')
            ->with([
                'productVariants.inventoryItem',
                'productMedia' => fn ($media) => $media->orderBy('position')->orderBy('id'),
            ]);

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['query'])) {
            $term = $validated['query'];
            $query->where(function ($inner) use ($term): void {
                $inner->where('title', 'like', "%{$term}%")
                    ->orWhere('vendor', 'like', "%{$term}%")
                    ->orWhereHas('productVariants', fn ($variants) => $variants->where('sku', 'like', "%{$term}%"));
            });
        }

        if (! empty($validated['collection_id'])) {
            $query->whereHas('collections', fn ($collections) => $collections->where('collections.id', $validated['collection_id']));
        }

        match ($validated['sort'] ?? 'updated_at_desc') {
            'title_asc' => $query->orderBy('title')->orderBy('id'),
            'title_desc' => $query->orderByDesc('title')->orderBy('id'),
            'created_at_asc' => $query->orderBy('created_at')->orderBy('id'),
            'created_at_desc' => $query->orderByDesc('created_at')->orderBy('id'),
            default => $query->orderByDesc('updated_at')->orderBy('id'),
        };

        $paginator = $query->paginate($validated['per_page'] ?? 25);

        return response()->json([
            'data' => ProductSummaryResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function store(StoreProductRequest $request, int $storeId): JsonResponse
    {
        Gate::authorize('create', Product::class);

        $store = $this->currentStore->get();

        abort_if($store === null || $store->getKey() !== $storeId, 404);

        try {
            $product = $this->products->create($store, $request->validated());
        } catch (InvalidProductTransitionException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return (new ProductResource($product))->response()->setStatusCode(201);
    }

    public function show(int $storeId, int $productId): JsonResponse
    {
        $product = $this->findProduct($productId);

        Gate::authorize('view', $product);

        $product->load([
            'productOptions' => fn ($query) => $query->orderBy('position'),
            'productOptions.productOptionValues' => fn ($query) => $query->orderBy('position'),
            'productVariants' => fn ($query) => $query->orderBy('position')->orderBy('id'),
            'productVariants.inventoryItem',
            'productVariants.optionValues.productOption',
            'productMedia' => fn ($query) => $query->orderBy('position')->orderBy('id'),
            'collections',
        ]);

        return (new ProductResource($product))->response();
    }

    public function update(UpdateProductRequest $request, int $storeId, int $productId): JsonResponse
    {
        $product = $this->findProduct($productId);

        Gate::authorize('update', $product);

        try {
            $updated = $this->products->update($product, $request->validated());
        } catch (InvalidProductTransitionException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return (new ProductResource($updated))->response();
    }

    public function destroy(int $storeId, int $productId): JsonResponse
    {
        $product = $this->findProduct($productId);

        Gate::authorize('archive', $product);

        $archived = $product->status === ProductStatus::Archived
            ? $product
            : $this->products->archive($product);

        return response()->json([
            'data' => [
                'id' => $archived->id,
                'status' => $archived->status->value,
                'updated_at' => $archived->updated_at?->toIso8601String(),
            ],
        ]);
    }

    protected function findProduct(int $productId): Product
    {
        return Product::query()
            ->where('store_id', $this->currentStore->id())
            ->whereKey($productId)
            ->firstOrFail();
    }
}
