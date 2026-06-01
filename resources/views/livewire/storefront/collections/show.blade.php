<div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    <x-storefront::breadcrumbs :items="[
        ['label' => __('Home'), 'url' => route('storefront.home')],
        ['label' => __('Collections'), 'url' => '/collections'],
        ['label' => $collection->title],
    ]" />

    <header class="mt-4">
        <h1 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ $collection->title }}</h1>
        @if ($collection->description_html)
            <div class="prose prose-zinc mt-3 max-w-2xl dark:prose-invert">{!! $collection->description_html !!}</div>
        @endif
    </header>

    <div class="mt-8 lg:grid lg:grid-cols-[16rem_1fr] lg:gap-8">
        {{-- Filter sidebar (desktop). --}}
        <aside class="hidden lg:block" aria-label="{{ __('Filters') }}">
            @if ($hasActiveFilters)
                <button type="button" wire:click="clearFilters" class="mb-4 text-sm font-medium text-blue-600 hover:underline dark:text-blue-400">
                    {{ __('Clear all filters') }}
                </button>
            @endif

            <div class="space-y-6">
                <div>
                    <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-200">
                        <input type="checkbox" wire:model.live="inStockOnly" class="rounded border-zinc-300 text-blue-600 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-800" />
                        {{ __('In stock') }}
                    </label>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Price') }}</h3>
                    <div class="mt-2 flex items-center gap-2">
                        <input type="number" wire:model.live.debounce.500ms="minPrice" placeholder="{{ __('Min') }}" min="0"
                               class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white" />
                        <span class="text-zinc-400">&ndash;</span>
                        <input type="number" wire:model.live.debounce.500ms="maxPrice" placeholder="{{ __('Max') }}" min="0"
                               class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white" />
                    </div>
                </div>

                @if (! empty($availableTypes))
                    <div>
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Product type') }}</h3>
                        <div class="mt-2 space-y-1.5">
                            @foreach ($availableTypes as $type)
                                <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-200">
                                    <input type="checkbox" value="{{ $type }}" wire:model.live="types" class="rounded border-zinc-300 text-blue-600 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-800" />
                                    {{ $type }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if (! empty($availableVendors))
                    <div>
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Vendor') }}</h3>
                        <div class="mt-2 space-y-1.5">
                            @foreach ($availableVendors as $vendor)
                                <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-200">
                                    <input type="checkbox" value="{{ $vendor }}" wire:model.live="vendors" class="rounded border-zinc-300 text-blue-600 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-800" />
                                    {{ $vendor }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </aside>

        <div>
            {{-- Toolbar. --}}
            <div class="flex items-center justify-between gap-4 border-b border-zinc-200 pb-4 dark:border-zinc-800">
                <p class="text-sm text-zinc-500 dark:text-zinc-400" aria-live="polite">
                    {{ trans_choice(':count product|:count products', $products->total(), ['count' => $products->total()]) }}
                </p>
                <div class="flex items-center gap-2">
                    <label for="sort" class="sr-only">{{ __('Sort by') }}</label>
                    <select id="sort" wire:model.live="sort"
                            class="rounded-lg border border-zinc-300 bg-white py-1.5 pl-3 pr-8 text-sm text-zinc-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">
                        <option value="featured">{{ __('Featured') }}</option>
                        <option value="price-asc">{{ __('Price: Low to High') }}</option>
                        <option value="price-desc">{{ __('Price: High to Low') }}</option>
                        <option value="newest">{{ __('Newest') }}</option>
                    </select>
                </div>
            </div>

            {{-- Product grid. --}}
            @if ($cards->isEmpty())
                <div class="py-20 text-center">
                    <flux:icon.magnifying-glass class="mx-auto size-12 text-zinc-300 dark:text-zinc-600" />
                    <h2 class="mt-4 text-lg font-semibold text-zinc-900 dark:text-white">{{ __('No products found') }}</h2>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Try adjusting your filters or browse our full collection.') }}</p>
                    @if ($hasActiveFilters)
                        <button type="button" wire:click="clearFilters"
                                class="mt-4 inline-flex items-center rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">
                            {{ __('Clear filters') }}
                        </button>
                    @endif
                </div>
            @else
                <div class="mt-6 grid grid-cols-2 gap-x-4 gap-y-8 md:grid-cols-3" wire:loading.class="opacity-50">
                    @foreach ($cards as $card)
                        <x-storefront::product-card :product="$card" />
                    @endforeach
                </div>

                <div class="mt-10">
                    <x-storefront::pagination :paginator="$products" />
                </div>
            @endif
        </div>
    </div>
</div>
