<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class ProductController extends Controller
{
    public function __construct(private readonly ProductService $products) {}

    public function index(Request $request, int $storeId): JsonResponse
    {
        $this->authorize('viewAny', Product::class);
        $validated = $request->validate([
            'status' => ['sometimes', 'in:draft,active,archived'],
            'query' => ['sometimes', 'string', 'max:200'],
            'collection_id' => ['sometimes', 'integer', Rule::exists('collections', 'id')->where('store_id', $storeId)],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort' => ['sometimes', 'in:title_asc,title_desc,created_at_asc,created_at_desc,updated_at_desc'],
        ]);
        [$sort, $direction] = match ($validated['sort'] ?? 'updated_at_desc') {
            'title_asc' => ['title', 'asc'], 'title_desc' => ['title', 'desc'],
            'created_at_asc' => ['created_at', 'asc'], 'created_at_desc' => ['created_at', 'desc'],
            default => ['updated_at', 'desc'],
        };
        $products = Product::withoutGlobalScopes()->where('store_id', $storeId)
            ->when(isset($validated['status']), fn ($query) => $query->where('status', $validated['status']))
            ->when(isset($validated['query']), fn ($query) => $query->where(function ($nested) use ($validated): void {
                $term = $validated['query'];
                $nested->where('title', 'like', "%{$term}%")->orWhere('vendor', 'like', "%{$term}%")
                    ->orWhereHas('variants', fn ($variants) => $variants->where('sku', 'like', "%{$term}%"));
            }))
            ->when(isset($validated['collection_id']), fn ($query) => $query->whereHas('collections', fn ($collections) => $collections->whereKey($validated['collection_id'])))
            ->with(['variants.inventoryItem', 'media', 'collections'])->orderBy($sort, $direction)->paginate($validated['per_page'] ?? 25);

        return response()->json(['data' => $products->items(), 'meta' => ['current_page' => $products->currentPage(), 'last_page' => $products->lastPage(), 'total' => $products->total()]]);
    }

    public function store(StoreProductRequest $request, int $storeId): JsonResponse
    {
        $this->authorize('create', Product::class);
        $data = $this->validated($request, $storeId);
        $product = $this->products->create(app('current_store'), $data);

        return response()->json(['data' => $product], 201);
    }

    public function show(int $storeId, int $productId): JsonResponse
    {
        $product = $this->product($storeId, $productId);
        $this->authorize('view', $product);

        return response()->json(['data' => $product->load(['options.values', 'variants.optionValues', 'variants.inventoryItem', 'media', 'collections'])]);
    }

    public function update(UpdateProductRequest $request, int $storeId, int $productId): JsonResponse
    {
        $product = $this->product($storeId, $productId);
        $this->authorize('update', $product);
        $data = $this->validated($request, $storeId, true);

        return response()->json(['data' => $this->products->update($product, $data)]);
    }

    public function destroy(int $storeId, int $productId): JsonResponse
    {
        $product = $this->product($storeId, $productId);
        $this->authorize('archive', $product);
        $this->products->transitionStatus($product, ProductStatus::Archived);

        return response()->json(['message' => 'Product archived.', 'data' => $product->refresh()]);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, int $storeId, bool $partial = false): array
    {
        $sometimes = $partial ? ['sometimes'] : ['required'];

        $uniqueSku = function (string $attribute, mixed $value, \Closure $fail) use ($storeId, $request): void {
            if ($value === null || trim((string) $value) === '') {
                return;
            }
            $query = ProductVariant::query()->where('sku', trim((string) $value))
                ->whereHas('product', fn ($products) => $products->withoutGlobalScopes()->where('store_id', $storeId));
            if ($request->route('productId') !== null) {
                preg_match('/^variants\.(\d+)\.sku$/', $attribute, $matches);
                $variantId = isset($matches[1]) ? data_get($request->input('variants'), $matches[1].'.id') : null;
                if ($variantId !== null) {
                    $query->whereKeyNot((int) $variantId);
                } elseif ($attribute === 'variant.sku') {
                    $defaultId = ProductVariant::query()
                        ->where('product_id', (int) $request->route('productId'))
                        ->orderByDesc('is_default')
                        ->value('id');
                    $query->when($defaultId !== null, fn ($variants) => $variants->whereKeyNot($defaultId));
                }
            }
            if ($query->exists()) {
                $fail('The SKU has already been taken for this store.');
            }
        };

        $validated = $request->validate([
            'title' => [...$sometimes, 'string', 'max:255'],
            'handle' => ['sometimes', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('products')->where('store_id', $storeId)->ignore($request->route('productId'))],
            'description_html' => ['nullable', 'string', 'max:65535'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'product_type' => ['nullable', 'string', 'max:255'],
            'tags' => ['sometimes', 'array', 'max:50'],
            'tags.*' => ['string', 'max:255'],
            'status' => ['sometimes', 'in:draft,active,archived'],
            'options' => ['sometimes', 'array', 'max:3'],
            'options.*.id' => ['sometimes', 'integer', Rule::exists('product_options', 'id')->where('product_id', (int) $request->route('productId'))],
            'options.*.name' => ['required_with:options', 'string', 'max:255'],
            'options.*.position' => ['sometimes', 'integer', 'min:0', 'max:3'],
            'options.*.values' => ['sometimes', 'array', 'min:1'],
            'variant' => ['sometimes', 'array'],
            'variant.price_amount' => ['sometimes', 'integer', 'min:0'],
            'variant.sku' => ['nullable', 'string', 'max:255', $uniqueSku],
            'variant.quantity_on_hand' => ['sometimes', 'integer'],
            'variants' => [$partial ? 'sometimes' : 'required_without:variant', 'array', 'min:1', 'max:100'],
            'variants.*.id' => ['sometimes', 'integer', Rule::exists('product_variants', 'id')->where('product_id', (int) $request->route('productId'))],
            'variants.*.sku' => ['nullable', 'string', 'max:255', 'distinct:ignore_case', $uniqueSku],
            'variants.*.barcode' => ['nullable', 'string', 'max:255'],
            'variants.*.price_amount' => ['required_with:variants', 'integer', 'min:0'],
            'variants.*.compare_at_amount' => ['nullable', 'integer', 'min:0'],
            'variants.*.currency' => ['sometimes', 'string', 'size:3'],
            'variants.*.weight_g' => ['nullable', 'integer', 'min:0'],
            'variants.*.requires_shipping' => ['sometimes', 'boolean'],
            'variants.*.is_default' => ['sometimes', 'boolean'],
            'variants.*.position' => ['sometimes', 'integer', 'min:0'],
            'variants.*.status' => ['sometimes', 'in:active,archived'],
            'variants.*.option_values' => ['sometimes', 'array'],
            'variants.*.option_values.*.id' => ['sometimes', 'integer'],
            'variants.*.option_values.*.option_name' => ['required_without:variants.*.option_values.*.id', 'string'],
            'variants.*.option_values.*.value' => ['required_without:variants.*.option_values.*.id', 'string'],
            'variants.*.inventory' => ['sometimes', 'array'],
            'variants.*.inventory.quantity_on_hand' => ['sometimes', 'integer', 'min:0'],
            'variants.*.inventory.policy' => ['sometimes', 'in:deny,continue'],
            'collections' => ['sometimes', 'array'],
            'collections.*' => ['integer', Rule::exists('collections', 'id')->where('store_id', $storeId)],
        ]);

        foreach ((array) ($validated['variants'] ?? []) as $index => $variant) {
            if (isset($variant['compare_at_amount']) && (int) $variant['compare_at_amount'] <= (int) $variant['price_amount']) {
                throw ValidationException::withMessages(["variants.{$index}.compare_at_amount" => 'The compare-at price must be greater than the price.']);
            }
        }

        if (isset($validated['options'], $validated['variants'])) {
            $validated['options'] = collect($validated['options'])->map(function (array $option, int $index) use ($validated): array {
                if (! empty($option['values'])) {
                    return $option;
                }
                $option['values'] = collect($validated['variants'])
                    ->flatMap(fn (array $variant): array => (array) ($variant['option_values'] ?? []))
                    ->filter(fn (array $value): bool => mb_strtolower((string) ($value['option_name'] ?? '')) === mb_strtolower($option['name']))
                    ->pluck('value')
                    ->filter()
                    ->unique(fn ($value) => mb_strtolower((string) $value))
                    ->values()
                    ->all();
                if ($option['values'] === []) {
                    throw ValidationException::withMessages(["options.{$index}.values" => 'Every option needs at least one value.']);
                }

                return $option;
            })->all();
        }

        if (isset($validated['options'])) {
            $combinationCount = collect($validated['options'])->reduce(function (int $count, array $option): int {
                $valueCount = collect((array) ($option['values'] ?? []))
                    ->map(fn (mixed $value): string => trim((string) (is_array($value) ? ($value['value'] ?? '') : $value)))
                    ->filter()
                    ->unique(fn (string $value): string => mb_strtolower($value))
                    ->count();

                return $count * max(1, $valueCount);
            }, 1);
            if ($combinationCount > 100) {
                throw ValidationException::withMessages(['options' => 'Product options may generate at most 100 variants.']);
            }
        }

        if (! empty($validated['variants'])) {
            $defaults = collect($validated['variants'])->filter(fn (array $variant): bool => (bool) ($variant['is_default'] ?? false));
            if ($defaults->count() > 1) {
                throw ValidationException::withMessages(['variants' => 'Exactly one variant may be the default.']);
            }
            if ($defaults->isEmpty()) {
                $validated['variants'][0]['is_default'] = true;
            }
        }

        return $validated;
    }

    private function product(int $storeId, int $productId): Product
    {
        return Product::withoutGlobalScopes()->where('store_id', $storeId)->findOrFail($productId);
    }
}
