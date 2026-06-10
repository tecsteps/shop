<div
    class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12"
    x-data="{ filtersOpen: false }"
    x-on:keydown.escape.window="filtersOpen = false"
>
    <x-storefront.breadcrumbs :items="[
        ['label' => __('Home'), 'url' => route('home')],
        ['label' => __('Search results')],
    ]" />

    {{-- Search header --}}
    <header class="mt-4">
        <h1 class="text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl dark:text-white">
            @if (trim($query) !== '')
                {{ trans_choice(':count result for ":query"|:count results for ":query"', $products->total(), ['count' => $products->total(), 'query' => $query]) }}
            @else
                {{ __('Search') }}
            @endif
        </h1>
    </header>

    {{-- Search input --}}
    <div class="mt-6 max-w-xl">
        <label class="relative block">
            <span class="sr-only">{{ __('Search products') }}</span>
            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-zinc-400" aria-hidden="true">
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
            </span>
            <input
                type="search"
                wire:model.live.debounce.500ms="query"
                placeholder="{{ __('Search products...') }}"
                class="block w-full rounded-lg border border-zinc-300 bg-white py-2.5 pr-3 pl-10 text-sm text-zinc-900 placeholder-zinc-400 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/30 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
            />
        </label>
    </div>

    {{-- Toolbar --}}
    <div class="mt-8 flex items-center justify-between gap-4 border-y border-zinc-200 py-3 dark:border-zinc-800">
        <button
            type="button"
            x-on:click="filtersOpen = true"
            class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 px-3 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 lg:hidden dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
        >
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
            </svg>
            {{ __('Filter') }}
        </button>

        <p class="hidden text-sm text-zinc-500 sm:block dark:text-zinc-400" aria-live="polite">
            {{ trans_choice(':count product|:count products', $products->total(), ['count' => $products->total()]) }}
        </p>

        <div class="flex items-center gap-2">
            <label for="search-sort" class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Sort by') }}</label>
            <select
                id="search-sort"
                wire:model.live="sort"
                class="rounded-lg border border-zinc-300 bg-white py-2 pr-8 pl-3 text-sm text-zinc-900 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/30 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
            >
                <option value="relevance">{{ __('Relevance') }}</option>
                <option value="price_asc">{{ __('Price: Low to High') }}</option>
                <option value="price_desc">{{ __('Price: High to Low') }}</option>
                <option value="newest">{{ __('Newest') }}</option>
            </select>
        </div>
    </div>

    <div class="mt-8 lg:grid lg:grid-cols-[16rem_1fr] lg:gap-10">
        {{-- Desktop filter sidebar --}}
        <aside class="hidden lg:block" aria-label="{{ __('Product filters') }}">
            @include('storefront.partials.collection-filters')
        </aside>

        {{-- Product grid --}}
        <div wire:loading.class="opacity-60" wire:target="query, sort, inStock, priceMin, priceMax, productTypes, vendors" class="transition-opacity">
            @if ($products->isEmpty())
                <div class="flex flex-col items-center justify-center py-24 text-center">
                    <svg class="size-12 text-zinc-300 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    @if (trim($query) !== '')
                        <p class="mt-4 text-base font-semibold text-zinc-900 dark:text-white">
                            {{ __('No results found for ":query".', ['query' => $query]) }}
                        </p>
                        <p class="mt-1 max-w-sm text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Try a different search term.') }}
                        </p>
                    @else
                        <p class="mt-4 text-base font-semibold text-zinc-900 dark:text-white">{{ __('Start searching') }}</p>
                        <p class="mt-1 max-w-sm text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Enter a search term above to find products.') }}
                        </p>
                    @endif
                </div>
            @else
                <div class="grid grid-cols-2 gap-x-4 gap-y-8 sm:gap-x-6 md:grid-cols-3">
                    @foreach ($products as $product)
                        <x-storefront.product-card :product="$product" />
                    @endforeach
                </div>

                <div class="mt-10">
                    <x-storefront.pagination :paginator="$products" />
                </div>
            @endif
        </div>
    </div>

    {{-- Mobile filter drawer --}}
    <div x-show="filtersOpen" x-cloak class="relative z-50 lg:hidden" role="dialog" aria-modal="true" aria-label="{{ __('Filters') }}">
        <div
            x-show="filtersOpen"
            x-transition.opacity.duration.200ms
            x-on:click="filtersOpen = false"
            class="fixed inset-0 bg-zinc-950/50"
            aria-hidden="true"
        ></div>
        <div
            x-show="filtersOpen"
            x-transition:enter="transition duration-200 ease-out"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition duration-150 ease-in"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="fixed inset-y-0 left-0 flex w-full max-w-xs flex-col bg-white shadow-xl dark:bg-zinc-900"
        >
            <div class="flex items-center justify-between border-b border-zinc-200 px-4 py-4 dark:border-zinc-800">
                <h2 class="text-base font-semibold text-zinc-900 dark:text-white">{{ __('Filters') }}</h2>
                <button
                    type="button"
                    x-on:click="filtersOpen = false"
                    class="rounded-lg p-2 text-zinc-700 transition hover:bg-zinc-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:text-zinc-300 dark:hover:bg-zinc-800"
                    aria-label="{{ __('Close filters') }}"
                >
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto px-4 py-5">
                @include('storefront.partials.collection-filters')
            </div>
            <div class="border-t border-zinc-200 p-4 dark:border-zinc-800">
                <button
                    type="button"
                    x-on:click="filtersOpen = false"
                    class="w-full rounded-lg bg-(--sf-primary,#1d4ed8) px-4 py-2.5 text-sm font-semibold text-white transition hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 focus-visible:ring-offset-2"
                >
                    {{ __('Apply') }}
                </button>
            </div>
        </div>
    </div>
</div>
