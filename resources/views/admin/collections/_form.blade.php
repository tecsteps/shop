@php
    $selected = old('product_ids', $selectedProductIds ?? []);
    $selected = array_map('intval', is_array($selected) ? $selected : []);
@endphp

<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <section class="rounded-lg border border-zinc-200 bg-white p-5">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-700">Details</h2>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <label class="block text-sm">
                    <span class="mb-1 block text-zinc-700">Title</span>
                    <input type="text" name="title" value="{{ old('title', $collection->title ?? '') }}" required class="w-full rounded-md border border-zinc-300 px-3 py-2">
                </label>

                <label class="block text-sm">
                    <span class="mb-1 block text-zinc-700">Handle</span>
                    <input type="text" name="handle" value="{{ old('handle', $collection->handle ?? '') }}" placeholder="auto-from-title" class="w-full rounded-md border border-zinc-300 px-3 py-2">
                </label>

                <label class="block text-sm">
                    <span class="mb-1 block text-zinc-700">Type</span>
                    <select name="type" class="w-full rounded-md border border-zinc-300 px-3 py-2">
                        @foreach (['manual', 'automated'] as $type)
                            <option value="{{ $type }}" @selected(old('type', $collection->type ?? 'manual') === $type)>
                                {{ ucfirst($type) }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="block text-sm">
                    <span class="mb-1 block text-zinc-700">Status</span>
                    <select name="status" class="w-full rounded-md border border-zinc-300 px-3 py-2">
                        @foreach ($statuses as $statusOption)
                            <option
                                value="{{ $statusOption->value }}"
                                @selected(old('status', $collection?->status?->value ?? \App\Enums\CollectionStatus::Active->value) === $statusOption->value)
                            >
                                {{ ucfirst($statusOption->value) }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="block text-sm sm:col-span-2">
                    <span class="mb-1 block text-zinc-700">Description</span>
                    <textarea name="description_html" rows="6" class="w-full rounded-md border border-zinc-300 px-3 py-2">{{ old('description_html', $collection->description_html ?? '') }}</textarea>
                </label>
            </div>
        </section>
    </div>

    <div class="space-y-6">
        <section class="rounded-lg border border-zinc-200 bg-white p-5">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-700">Product Assignment</h2>
            <p class="mt-2 text-xs text-zinc-500">Basic manual assignment for this collection.</p>

            <div class="mt-3 max-h-96 space-y-2 overflow-auto rounded-md border border-zinc-200 p-3">
                @forelse ($products as $product)
                    <label class="flex items-start gap-2 rounded-md px-2 py-1 hover:bg-zinc-50">
                        <input
                            type="checkbox"
                            name="product_ids[]"
                            value="{{ $product->id }}"
                            @checked(in_array($product->id, $selected, true))
                            class="mt-1 rounded border-zinc-300 text-zinc-900"
                        >
                        <span class="text-sm">
                            <span class="block font-medium text-zinc-900">{{ $product->title }}</span>
                            <span class="block text-xs text-zinc-500">{{ $product->handle }}</span>
                        </span>
                    </label>
                @empty
                    <p class="text-sm text-zinc-500">No products found.</p>
                @endforelse
            </div>
        </section>
    </div>
</div>
