<div class="space-y-10">
    <header class="space-y-3">
        <nav class="text-xs text-zinc-500 dark:text-zinc-400" aria-label="Breadcrumb">
            <a href="{{ route('storefront.collections.index') }}" class="hover:text-zinc-900 dark:hover:text-zinc-100">Collections</a>
            <span class="px-1">/</span>
            <span class="text-zinc-700 dark:text-zinc-300">{{ $collection->title }}</span>
        </nav>
        <h1 class="text-3xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-50 sm:text-4xl">{{ $collection->title }}</h1>
        @if ($collection->description_html)
            <div class="prose prose-sm prose-zinc max-w-2xl text-sm text-zinc-600 dark:prose-invert dark:text-zinc-400">
                {!! $collection->description_html !!}
            </div>
        @endif
    </header>

    <div class="flex items-center justify-between border-b border-zinc-200 pb-4 dark:border-zinc-800">
        <p class="text-sm text-zinc-500 dark:text-zinc-400">
            {{ $products->total() }} {{ \Illuminate\Support\Str::plural('product', $products->total()) }}
        </p>
        <label class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
            <span>Sort</span>
            <select
                wire:model.live="sort"
                class="rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm text-zinc-900 focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
            >
                <option value="newest">Newest</option>
                <option value="title_asc">Title, A to Z</option>
                <option value="position">Featured</option>
            </select>
        </label>
    </div>

    @if ($products->isEmpty())
        <div class="rounded-2xl border border-dashed border-zinc-300 bg-zinc-50 p-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm text-zinc-600 dark:text-zinc-400">No products in this collection yet.</p>
        </div>
    @else
        <div class="grid grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($products as $product)
                <x-storefront.product-card :product="$product" />
            @endforeach
        </div>

        <div>
            {{ $products->links() }}
        </div>
    @endif
</div>
