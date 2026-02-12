@php
    $defaultVariant = $product?->defaultVariant;
    $inventoryItem = $defaultVariant?->inventoryItem;
@endphp

<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <section class="rounded-lg border border-zinc-200 bg-white p-5">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-700">Basic</h2>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <label class="block text-sm">
                    <span class="mb-1 block text-zinc-700">Title</span>
                    <input type="text" name="title" value="{{ old('title', $product->title ?? '') }}" required class="w-full rounded-md border border-zinc-300 px-3 py-2">
                </label>

                <label class="block text-sm">
                    <span class="mb-1 block text-zinc-700">Handle</span>
                    <input type="text" name="handle" value="{{ old('handle', $product->handle ?? '') }}" placeholder="auto-from-title" class="w-full rounded-md border border-zinc-300 px-3 py-2">
                </label>

                <label class="block text-sm">
                    <span class="mb-1 block text-zinc-700">Vendor</span>
                    <input type="text" name="vendor" value="{{ old('vendor', $product->vendor ?? '') }}" class="w-full rounded-md border border-zinc-300 px-3 py-2">
                </label>

                <label class="block text-sm">
                    <span class="mb-1 block text-zinc-700">Product type</span>
                    <input type="text" name="product_type" value="{{ old('product_type', $product->product_type ?? '') }}" class="w-full rounded-md border border-zinc-300 px-3 py-2">
                </label>

                <label class="block text-sm sm:col-span-2">
                    <span class="mb-1 block text-zinc-700">Tags</span>
                    <input
                        type="text"
                        name="tags"
                        value="{{ old('tags', isset($product) && is_array($product->tags) ? implode(', ', $product->tags) : '') }}"
                        placeholder="summer, new"
                        class="w-full rounded-md border border-zinc-300 px-3 py-2"
                    >
                </label>

                <label class="block text-sm sm:col-span-2">
                    <span class="mb-1 block text-zinc-700">Description</span>
                    <textarea name="description_html" rows="6" class="w-full rounded-md border border-zinc-300 px-3 py-2">{{ old('description_html', $product->description_html ?? '') }}</textarea>
                </label>
            </div>
        </section>
    </div>

    <div class="space-y-6">
        <section class="rounded-lg border border-zinc-200 bg-white p-5">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-700">Status</h2>
            <div class="mt-3">
                <select name="status" class="w-full rounded-md border border-zinc-300 px-3 py-2 text-sm">
                    @foreach ($statuses as $statusOption)
                        <option
                            value="{{ $statusOption->value }}"
                            @selected(old('status', $product?->status?->value ?? \App\Enums\ProductStatus::Draft->value) === $statusOption->value)
                        >
                            {{ ucfirst($statusOption->value) }}
                        </option>
                    @endforeach
                </select>
            </div>
        </section>

        <section class="rounded-lg border border-zinc-200 bg-white p-5">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-700">Default Variant</h2>
            <div class="mt-4 space-y-4">
                <label class="block text-sm">
                    <span class="mb-1 block text-zinc-700">SKU</span>
                    <input type="text" name="sku" value="{{ old('sku', $defaultVariant?->sku ?? '') }}" class="w-full rounded-md border border-zinc-300 px-3 py-2">
                </label>

                <label class="block text-sm">
                    <span class="mb-1 block text-zinc-700">Price (minor units)</span>
                    <input type="number" name="price_amount" min="0" value="{{ old('price_amount', $defaultVariant?->price_amount ?? 0) }}" class="w-full rounded-md border border-zinc-300 px-3 py-2">
                    <span class="mt-1 block text-xs text-zinc-500">Stored in cents for {{ strtoupper($currency) }}.</span>
                </label>

                <label class="block text-sm">
                    <span class="mb-1 block text-zinc-700">Quantity on hand</span>
                    <input type="number" name="inventory_quantity" min="0" value="{{ old('inventory_quantity', $inventoryItem?->quantity_on_hand ?? 0) }}" class="w-full rounded-md border border-zinc-300 px-3 py-2">
                </label>

                <label class="block text-sm">
                    <span class="mb-1 block text-zinc-700">Inventory policy</span>
                    <select name="inventory_policy" class="w-full rounded-md border border-zinc-300 px-3 py-2">
                        @foreach ($inventoryPolicies as $policy)
                            <option
                                value="{{ $policy->value }}"
                                @selected(old('inventory_policy', $inventoryItem?->policy?->value ?? \App\Enums\InventoryPolicy::Deny->value) === $policy->value)
                            >
                                {{ $policy->value }}
                            </option>
                        @endforeach
                    </select>
                </label>
            </div>
        </section>
    </div>
</div>
