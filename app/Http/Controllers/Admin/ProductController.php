<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends AdminController
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->string('search'));
        $status = (string) $request->string('status', '');

        $products = Product::query()
            ->with('defaultVariant.inventoryItem')
            ->withCount('variants')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhere('handle', 'like', "%{$search}%")
                        ->orWhere('vendor', 'like', "%{$search}%")
                        ->orWhere('product_type', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, $this->enumValues(ProductStatus::class), true), function ($query) use ($status): void {
                $query->where('status', $status);
            })
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'search' => $search,
            'status' => $status,
            'statuses' => ProductStatus::cases(),
            'currency' => $this->currentStore()->default_currency,
        ]);
    }

    public function create(): View
    {
        return view('admin.products.create', [
            'statuses' => ProductStatus::cases(),
            'inventoryPolicies' => InventoryPolicy::cases(),
            'currency' => $this->currentStore()->default_currency,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateProduct($request);
        $store = $this->currentStore();

        DB::transaction(function () use ($validated, $store): void {
            $handle = $this->uniqueHandle(
                $validated['handle'] ?: $validated['title'],
                fn (string $candidate): bool => Product::query()->where('handle', $candidate)->exists(),
                'product'
            );

            $product = Product::query()->create([
                'store_id' => $store->id,
                'title' => $validated['title'],
                'handle' => $handle,
                'status' => $validated['status'],
                'description_html' => $validated['description_html'],
                'vendor' => $validated['vendor'],
                'product_type' => $validated['product_type'],
                'tags' => $this->parseTags($validated['tags']),
                'published_at' => $validated['status'] === ProductStatus::Active->value ? now() : null,
            ]);

            $defaultVariant = $product->variants()->create([
                'sku' => $validated['sku'],
                'price_amount' => $validated['price_amount'],
                'compare_at_amount' => null,
                'currency' => $store->default_currency,
                'requires_shipping' => true,
                'is_default' => true,
                'position' => 0,
                'status' => 'active',
            ]);

            $defaultVariant->inventoryItem()->create([
                'store_id' => $store->id,
                'quantity_on_hand' => $validated['inventory_quantity'],
                'quantity_reserved' => 0,
                'policy' => $validated['inventory_policy'],
            ]);
        });

        return redirect()->route('admin.products.index')
            ->with('status', 'Product created.');
    }

    public function edit(Product $product): View
    {
        $product->load('defaultVariant.inventoryItem');

        return view('admin.products.edit', [
            'product' => $product,
            'statuses' => ProductStatus::cases(),
            'inventoryPolicies' => InventoryPolicy::cases(),
            'currency' => $this->currentStore()->default_currency,
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $this->validateProduct($request);
        $store = $this->currentStore();

        DB::transaction(function () use ($validated, $product, $store): void {
            $handle = $this->uniqueHandle(
                $validated['handle'] ?: $validated['title'],
                fn (string $candidate): bool => Product::query()
                    ->where('handle', $candidate)
                    ->whereKeyNot($product->id)
                    ->exists(),
                'product'
            );

            $product->update([
                'title' => $validated['title'],
                'handle' => $handle,
                'status' => $validated['status'],
                'description_html' => $validated['description_html'],
                'vendor' => $validated['vendor'],
                'product_type' => $validated['product_type'],
                'tags' => $this->parseTags($validated['tags']),
                'published_at' => $validated['status'] === ProductStatus::Active->value
                    ? ($product->published_at ?? now())
                    : null,
            ]);

            $defaultVariant = $product->defaultVariant()->first();

            if (! $defaultVariant) {
                $defaultVariant = $product->variants()->create([
                    'currency' => $store->default_currency,
                    'requires_shipping' => true,
                    'is_default' => true,
                    'position' => 0,
                    'status' => 'active',
                ]);
            }

            $defaultVariant->update([
                'sku' => $validated['sku'],
                'price_amount' => $validated['price_amount'],
                'currency' => $store->default_currency,
            ]);

            $inventoryItem = $defaultVariant->inventoryItem()->firstOrCreate(
                ['variant_id' => $defaultVariant->id],
                [
                    'store_id' => $store->id,
                    'quantity_on_hand' => 0,
                    'quantity_reserved' => 0,
                    'policy' => InventoryPolicy::Deny->value,
                ]
            );

            $inventoryItem->update([
                'quantity_on_hand' => $validated['inventory_quantity'],
                'policy' => $validated['inventory_policy'],
            ]);
        });

        return redirect()->route('admin.products.edit', $product)
            ->with('status', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->status !== ProductStatus::Draft) {
            return back()->withErrors([
                'delete' => 'Only draft products can be deleted. Archive active products instead.',
            ]);
        }

        $hasOrderLines = $product->variants()->whereHas('orderLines')->exists();

        if ($hasOrderLines) {
            return back()->withErrors([
                'delete' => 'This product is referenced by orders and cannot be deleted.',
            ]);
        }

        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('status', 'Product deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateProduct(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'handle' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'status' => ['required', Rule::in($this->enumValues(ProductStatus::class))],
            'description_html' => ['nullable', 'string'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'product_type' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'string'],
            'sku' => ['nullable', 'string', 'max:255'],
            'price_amount' => ['required', 'integer', 'min:0'],
            'inventory_quantity' => ['required', 'integer', 'min:0'],
            'inventory_policy' => ['required', Rule::in($this->enumValues(InventoryPolicy::class))],
        ]);
    }

    /**
     * @return list<string>
     */
    protected function parseTags(?string $rawTags): array
    {
        if ($rawTags === null || trim($rawTags) === '') {
            return [];
        }

        $tags = array_map('trim', explode(',', $rawTags));
        $tags = array_filter($tags, static fn (string $tag): bool => $tag !== '');

        return array_values(array_unique($tags));
    }
}
