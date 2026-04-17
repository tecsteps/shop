<div class="flex flex-col gap-8">
    <div>
        <flux:heading size="xl">{{ $collection->title }}</flux:heading>
        @if ($collection->description_html)
            <div class="prose mt-2 text-zinc-600 dark:prose-invert dark:text-zinc-400">
                {!! $collection->description_html !!}
            </div>
        @endif
    </div>

    <div class="flex items-center justify-between">
        <div class="text-sm text-zinc-500">{{ $products->total() }} products</div>
        <select wire:model.live="sort" class="rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-800">
            <option value="newest">Newest</option>
            <option value="price_asc">Title A–Z</option>
            <option value="price_desc">Title Z–A</option>
        </select>
    </div>

    @if ($products->isEmpty())
        <div class="rounded-xl bg-zinc-50 p-10 text-center text-zinc-500 dark:bg-zinc-900">
            No products in this collection.
        </div>
    @else
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4" data-testid="product-grid">
            @foreach ($products as $product)
                <x-product-card :product="$product" />
            @endforeach
        </div>

        <div>{{ $products->links() }}</div>
    @endif
</div>
