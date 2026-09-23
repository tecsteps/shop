<div>
@if ($isOpen ?? false)
    <div class="fixed inset-0 z-[70] flex items-start justify-center overflow-y-auto bg-stone-950/60 px-3 py-4 sm:px-6 sm:py-[8vh]" wire:click.self="close" role="presentation">
        <section x-trap.noscroll="true" x-on:keydown.escape.window="$wire.close()" role="dialog" aria-modal="true" aria-labelledby="storefront-search-title" class="w-full max-w-2xl overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-2xl dark:border-stone-700 dark:bg-stone-950">
            <h2 id="storefront-search-title" class="sr-only">Search</h2>
            <form action="/search" method="get" wire:submit="search" class="flex items-center gap-3 border-b border-stone-200 px-4 dark:border-stone-800 sm:px-5">
                @include('storefront.components.icon', ['name' => 'search', 'class' => 'size-5 shrink-0 text-stone-500'])
                <label for="storefront-search-input" class="sr-only">Search products and collections</label>
                <input id="storefront-search-input" type="search" name="q" wire:model.live.debounce.300ms="query" placeholder="Search products and collections" autocomplete="off" class="h-16 min-w-0 flex-1 bg-transparent text-base outline-none placeholder:text-stone-400 sm:text-lg" autofocus aria-controls="storefront-search-results" aria-expanded="{{ !empty($products ?? []) || !empty($collections ?? []) ? 'true' : 'false' }}">
                <button type="button" wire:click="close" class="inline-flex size-11 shrink-0 items-center justify-center rounded-full text-stone-600 hover:bg-stone-100 focus-visible:outline-2 focus-visible:outline-blue-700 dark:text-stone-300 dark:hover:bg-stone-800" aria-label="Close search">@include('storefront.components.icon', ['name' => 'close', 'class' => 'size-5'])</button>
            </form>
            <div id="storefront-search-results" class="max-h-[65vh] overflow-y-auto p-4 sm:p-5" role="listbox" aria-label="Search suggestions">
                @if (!empty($query ?? ''))
                    @if (!empty($products ?? []))
                        <h3 class="mb-2 text-xs font-semibold uppercase tracking-[0.14em] text-stone-500">Products</h3>
                        <ul class="grid gap-1">
                            @foreach ($products as $product)
                                @php($productTitle = data_get($product, 'title', 'Product'))
                                <li><a role="option" href="{{ url('/products/'.data_get($product, 'handle', '')) }}" class="flex min-h-14 items-center gap-3 rounded-xl px-2 py-2 hover:bg-stone-100 focus-visible:outline-2 focus-visible:outline-blue-700 dark:hover:bg-stone-900"><span class="size-11 shrink-0 overflow-hidden rounded-lg bg-stone-100 dark:bg-stone-800">@if (data_get($product, 'media.0.url'))<img src="{{ data_get($product, 'media.0.url') }}" alt="{{ data_get($product, 'media.0.alt', $productTitle) }}" loading="lazy" class="size-full object-cover">@else<span class="flex size-full items-center justify-center">@include('storefront.components.icon', ['name' => 'bag', 'class' => 'size-5 text-stone-400'])</span>@endif</span><span class="min-w-0 flex-1 truncate text-sm font-medium">{{ $productTitle }}</span>@include('storefront.components.price', ['amount' => (int) data_get($product, 'price_amount', 0), 'currency' => data_get($product, 'currency', data_get($currentStore ?? null, 'currency', 'EUR'))])</a></li>
                            @endforeach
                        </ul>
                    @endif
                    @if (!empty($collections ?? []))
                        <h3 class="mb-2 mt-5 text-xs font-semibold uppercase tracking-[0.14em] text-stone-500">Collections</h3>
                        <ul class="grid gap-1">
                            @foreach ($collections as $collection)
                                <li><a role="option" href="{{ url('/collections/'.data_get($collection, 'handle', '')) }}" class="flex min-h-11 items-center justify-between rounded-xl px-3 text-sm font-medium hover:bg-stone-100 dark:hover:bg-stone-900">{{ data_get($collection, 'title', 'Collection') }}@include('storefront.components.icon', ['name' => 'arrow-right', 'class' => 'size-4 text-stone-400'])</a></li>
                            @endforeach
                        </ul>
                    @endif
                    @if (empty($products ?? []) && empty($collections ?? []))
                        <p class="py-8 text-center text-sm text-stone-500 dark:text-stone-400">No results for “{{ $query }}”.</p>
                    @endif
                    <a href="{{ url('/search?q='.urlencode($query)) }}" class="mt-4 flex min-h-11 items-center justify-center gap-2 rounded-xl border border-stone-200 text-sm font-semibold hover:bg-stone-50 dark:border-stone-800 dark:hover:bg-stone-900">View all results @include('storefront.components.icon', ['name' => 'arrow-right', 'class' => 'size-4'])</a>
                @else
                    <p class="py-8 text-center text-sm text-stone-500 dark:text-stone-400">Search the store for products and collections.</p>
                @endif
            </div>
        </section>
    </div>
@endif
</div>
