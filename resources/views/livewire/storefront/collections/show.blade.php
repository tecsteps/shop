<div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    <x-storefront::breadcrumbs :items="[
        ['label' => 'Home', 'url' => route('storefront.home')],
        ['label' => 'Collections', 'url' => route('storefront.collections.index')],
        ['label' => $collection->title],
    ]" />

    {{-- Collection header --}}
    <div class="mt-4 max-w-3xl">
        <h1 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl dark:text-white">{{ $collection->title }}</h1>
        @if (! empty($collection->description_html))
            <div class="storefront-prose mt-4 text-gray-600 dark:text-gray-300">
                {!! $collection->description_html !!}
            </div>
        @endif
    </div>

    {{-- Toolbar --}}
    <div class="mt-8 flex items-center justify-between gap-4 border-y border-gray-200 py-3 dark:border-gray-800">
        <button type="button"
                @click="$dispatch('toggle-filters')"
                class="inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-hidden focus:ring-2 focus:ring-blue-500 lg:hidden dark:text-gray-200 dark:hover:bg-gray-800"
                aria-label="Toggle filters">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
            </svg>
            Filters
        </button>
        <p class="hidden text-sm text-gray-500 lg:block dark:text-gray-400" aria-live="polite">{{ $products->total() }} {{ str('product')->plural($products->total()) }}</p>
        <div class="flex items-center gap-2">
            <label for="sort" class="text-sm text-gray-600 dark:text-gray-300">Sort by</label>
            <select id="sort"
                    wire:model.live="sort"
                    class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-hidden focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                @foreach ($sortOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Active filter pills --}}
    @if (count($activeFilters) > 0)
        <div class="mt-4 flex flex-wrap items-center gap-2" aria-live="polite">
            @foreach ($activeFilters as $filter)
                <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                    {{ $filter }}
                </span>
            @endforeach
            <button type="button" wire:click="clearFilters" class="text-xs font-medium text-blue-600 underline-offset-2 hover:underline focus:outline-hidden focus:ring-2 focus:ring-blue-500 rounded dark:text-blue-400">
                Clear all
            </button>
        </div>
    @endif

    <div class="mt-8 flex gap-8" x-data="{ filtersOpen: false }" @toggle-filters.window="filtersOpen = !filtersOpen">
        {{-- Filter sidebar (desktop persistent / mobile drawer) --}}
        <aside class="hidden w-64 shrink-0 lg:block" aria-label="Product filters">
            @include('livewire.storefront.collections.partials.filters')
        </aside>

        {{-- Mobile filter drawer --}}
        <div x-show="filtersOpen" x-cloak class="lg:hidden" role="dialog" aria-modal="true" aria-label="Filters">
            <div x-show="filtersOpen"
                 x-transition:enter="transition-opacity ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 @click="filtersOpen = false"
                 class="fixed inset-0 z-40 bg-gray-900/50 dark:bg-black/60" aria-hidden="true"></div>
            <div x-show="filtersOpen"
                 x-transition:enter="transition ease-out duration-200" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
                 @keydown.escape.window="filtersOpen = false"
                 class="fixed inset-y-0 left-0 z-50 flex w-80 max-w-full flex-col bg-white shadow-xl dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-gray-200 px-4 py-4 dark:border-gray-800">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Filters</h2>
                    <button type="button" @click="filtersOpen = false"
                            class="rounded-md p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-hidden focus:ring-2 focus:ring-blue-500 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
                            aria-label="Close filters">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto px-4 py-4">
                    @include('livewire.storefront.collections.partials.filters')
                </div>
                <div class="border-t border-gray-200 px-4 py-4 dark:border-gray-800">
                    <button type="button" @click="filtersOpen = false"
                            class="w-full rounded-md bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 focus:outline-hidden focus:ring-2 focus:ring-blue-500">
                        Apply
                    </button>
                </div>
            </div>
        </div>

        {{-- Product grid --}}
        <div class="min-w-0 flex-1" aria-live="polite">
            @if ($products->isEmpty())
                <div class="flex flex-col items-center gap-3 py-24 text-center">
                    <svg class="size-12 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">No products found</p>
                    <p class="max-w-md text-sm text-gray-500 dark:text-gray-400">Try adjusting your filters or browse our full collection.</p>
                    @if (count($activeFilters) > 0)
                        <button type="button" wire:click="clearFilters"
                                class="mt-2 rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-hidden focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
                            Clear filters
                        </button>
                    @endif
                </div>
            @else
                <div class="grid grid-cols-2 gap-x-4 gap-y-8 md:grid-cols-3" wire:loading.class="opacity-60">
                    @foreach ($products as $product)
                        <x-storefront::product-card :product="$product" wire:key="product-{{ $product->id }}" />
                    @endforeach
                </div>
                <div class="mt-10">
                    <x-storefront::pagination :paginator="$products" />
                </div>
            @endif
        </div>
    </div>
</div>
