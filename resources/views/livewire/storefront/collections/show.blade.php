<div x-data="{ filtersOpen: false }">
    {{-- Collection header --}}
    <div class="mx-auto max-w-7xl px-4 pt-8 sm:px-6 lg:px-8">
        <x-storefront-breadcrumbs :items="[
            ['label' => 'Home', 'url' => route('storefront.home')],
            ['label' => 'Collections', 'url' => route('storefront.collections.index')],
            ['label' => $this->collection->title],
        ]" />

        <h1 class="mt-6 text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl dark:text-white">
            {{ $this->collection->title }}
        </h1>

        @if ($this->collection->description_html)
            <div class="mt-4 max-w-3xl space-y-3 text-zinc-600 [&_a]:font-medium [&_a]:text-blue-600 [&_a]:underline [&_a]:underline-offset-2 [&_h2]:text-lg [&_h2]:font-semibold [&_h2]:text-zinc-900 [&_li]:mb-1 [&_ol]:list-decimal [&_ol]:pl-5 [&_p]:leading-relaxed [&_ul]:list-disc [&_ul]:pl-5 dark:text-zinc-300 dark:[&_h2]:text-white">
                {!! $this->collection->description_html !!}
            </div>
        @endif
    </div>

    <div class="mx-auto max-w-7xl px-4 pb-16 pt-8 sm:px-6 lg:px-8">
        <div class="lg:grid lg:grid-cols-[256px_minmax(0,1fr)] lg:gap-8">
            {{-- Desktop filter sidebar --}}
            <aside class="hidden lg:block" aria-label="Product filters">
                <div class="sticky top-24 rounded-2xl border border-zinc-200 bg-zinc-50 p-5 dark:border-zinc-800 dark:bg-zinc-900">
                    @include('storefront.partials.filter-panel')
                </div>
            </aside>

            <div class="mt-6 lg:mt-0">
                {{-- Toolbar --}}
                <div class="flex items-center justify-between gap-4">
                    <button
                        type="button"
                        @click="filtersOpen = true"
                        class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 px-4 py-2.5 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50 lg:hidden dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-900"
                    >
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z" />
                        </svg>
                        Filter
                        @if ($this->activeFilterCount > 0)
                            <span class="flex h-5 min-w-5 items-center justify-center rounded-full bg-blue-600 px-1 text-[11px] font-semibold text-white">{{ $this->activeFilterCount }}</span>
                        @endif
                    </button>

                    <p class="text-sm text-zinc-500 dark:text-zinc-400" aria-live="polite">
                        {{ $this->products->total() }} {{ $this->products->total() === 1 ? 'product' : 'products' }}
                    </p>

                    <label class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                        <span class="sr-only sm:not-sr-only">Sort</span>
                        <select
                            wire:model.live="sort"
                            class="rounded-lg border border-zinc-300 bg-white py-2 pl-3 pr-8 text-sm font-medium text-zinc-900 transition focus:border-zinc-900 focus:outline-none focus:ring-2 focus:ring-zinc-900/20 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white dark:focus:border-white dark:focus:ring-white/20"
                        >
                            <option value="featured">Featured</option>
                            <option value="price_asc">Price: Low to High</option>
                            <option value="price_desc">Price: High to Low</option>
                            <option value="newest">Newest</option>
                            <option value="best_selling">Best Selling</option>
                        </select>
                    </label>
                </div>

                {{-- Active filter pills --}}
                @if ($this->activeFilterCount > 0)
                    <div class="mt-4 flex flex-wrap items-center gap-2" aria-label="Active filters">
                        @if ($this->inStockOnly)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                                In stock
                                <button type="button" wire:click="removeFilter('in_stock', '1')" aria-label="Remove in stock filter" class="text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200">
                                    <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12" /></svg>
                                </button>
                            </span>
                        @endif

                        @foreach ($this->vendors as $vendor)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                                {{ $vendor }}
                                <button type="button" wire:click="removeFilter('vendor', '{{ $vendor }}')" aria-label="Remove {{ $vendor }} filter" class="text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200">
                                    <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12" /></svg>
                                </button>
                            </span>
                        @endforeach

                        @foreach ($this->types as $type)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                                {{ $type }}
                                <button type="button" wire:click="removeFilter('type', '{{ $type }}')" aria-label="Remove {{ $type }} filter" class="text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200">
                                    <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12" /></svg>
                                </button>
                            </span>
                        @endforeach

                        @if ($this->priceMin !== null)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                                Min {{ $this->money($this->priceMin) }}
                                <button type="button" wire:click="removeFilter('price_min', '1')" aria-label="Remove minimum price filter" class="text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200">
                                    <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12" /></svg>
                                </button>
                            </span>
                        @endif

                        @if ($this->priceMax !== null)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                                Max {{ $this->money($this->priceMax) }}
                                <button type="button" wire:click="removeFilter('price_max', '1')" aria-label="Remove maximum price filter" class="text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200">
                                    <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12" /></svg>
                                </button>
                            </span>
                        @endif

                        <button
                            type="button"
                            wire:click="clearFilters"
                            class="text-xs font-medium text-blue-600 transition hover:underline dark:text-blue-400"
                        >
                            Clear all
                        </button>
                    </div>
                @endif

                {{-- Product grid --}}
                <div
                    class="mt-6 grid grid-cols-2 gap-4 sm:gap-6 md:grid-cols-3"
                    wire:loading.class="opacity-60"
                >
                    @forelse ($this->products as $product)
                        <x-storefront-product-card :product="$product" />
                    @empty
                        <div class="col-span-full flex flex-col items-center py-20 text-center">
                            <svg class="size-12 text-zinc-300 dark:text-zinc-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="11" cy="11" r="8" />
                                <path d="m21 21-4.35-4.35" />
                            </svg>
                            <p class="mt-4 text-lg font-semibold text-zinc-900 dark:text-white">No products found</p>
                            <p class="mt-2 max-w-sm text-sm text-zinc-500 dark:text-zinc-400">
                                Try adjusting your filters or browse our full collection.
                            </p>
                            <button
                                type="button"
                                wire:click="clearFilters"
                                class="mt-6 rounded-lg border border-zinc-300 px-5 py-2.5 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-900"
                            >
                                Clear filters
                            </button>
                        </div>
                    @endforelse
                </div>

                <x-storefront-pagination :paginator="$this->products" />
            </div>
        </div>
    </div>

    {{-- Mobile filter drawer --}}
    <div
        x-show="filtersOpen"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        class="fixed inset-0 z-50 lg:hidden"
        role="dialog"
        aria-modal="true"
        aria-label="Filters"
    >
        <div class="absolute inset-0 bg-zinc-950/50 backdrop-blur-sm" @click="filtersOpen = false" aria-hidden="true"></div>

        <div class="absolute inset-y-0 left-0 flex w-full max-w-xs flex-col bg-white shadow-2xl dark:bg-zinc-950">
            <div class="flex items-center justify-between border-b border-zinc-200 px-4 py-3 dark:border-zinc-800">
                <span class="text-base font-semibold text-zinc-900 dark:text-white">Filters</span>
                <button
                    type="button"
                    @click="filtersOpen = false"
                    aria-label="Close filters"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-900 dark:hover:bg-zinc-800 dark:hover:text-white"
                >
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-4 py-5">
                @include('storefront.partials.filter-panel')
            </div>

            <div class="border-t border-zinc-200 p-4 dark:border-zinc-800">
                <button
                    type="button"
                    @click="filtersOpen = false"
                    class="w-full rounded-lg bg-zinc-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200"
                >
                    Apply
                </button>
            </div>
        </div>
    </div>
</div>
