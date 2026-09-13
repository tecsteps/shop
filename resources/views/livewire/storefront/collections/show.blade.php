<div class="mx-auto max-w-7xl px-4 py-8">
    <x-storefront.breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => 'Collections', 'url' => route('storefront.collections.index')], ['label' => $collection->title]]" />

    <h1 class="mt-4 text-3xl font-bold">{{ $collection->title }}</h1>
    @if ($collection->description_html)
        <div class="prose mt-2 max-w-3xl dark:prose-invert">{!! $collection->description_html !!}</div>
    @endif

    <div class="mt-6 flex items-center justify-between gap-4 border-y border-zinc-200 py-3 dark:border-zinc-800">
        <div class="flex items-center gap-3">
            <flux:button wire:click="$toggle('showMobileFilters')" variant="ghost" size="sm" class="lg:hidden">Filter</flux:button>
            <p class="text-sm text-zinc-500 dark:text-zinc-400" aria-live="polite">{{ $products->total() }} products</p>
        </div>
        <div class="flex items-center gap-2">
            <label for="sort" class="text-sm">Sort</label>
            <select id="sort" wire:model.live="sort" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                <option value="featured">Featured</option>
                <option value="price_asc">Price: Low to High</option>
                <option value="price_desc">Price: High to Low</option>
                <option value="newest">Newest</option>
                <option value="best_selling">Best Selling</option>
            </select>
        </div>
    </div>

    @if ($this->hasActiveFilters())
        <div class="mt-4 flex flex-wrap items-center gap-2">
            @if ($inStock)
                <span class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-3 py-1 text-xs dark:bg-zinc-800">In stock <button type="button" wire:click="$set('inStock', false)" aria-label="Remove in stock filter">x</button></span>
            @endif
            @foreach ($vendors as $vendor)
                <span wire:key="pill-vendor-{{ $loop->index }}" class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-3 py-1 text-xs dark:bg-zinc-800">{{ $vendor }} <button type="button" wire:click="removeVendor('{{ $vendor }}')" aria-label="Remove {{ $vendor }} filter">x</button></span>
            @endforeach
            @foreach ($types as $type)
                <span wire:key="pill-type-{{ $loop->index }}" class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-3 py-1 text-xs dark:bg-zinc-800">{{ $type }} <button type="button" wire:click="removeType('{{ $type }}')" aria-label="Remove {{ $type }} filter">x</button></span>
            @endforeach
            <button type="button" wire:click="clearFilters" class="text-xs text-blue-600 hover:underline dark:text-blue-400">Clear all</button>
        </div>
    @endif

    <div class="mt-6 flex gap-8">
        <aside class="hidden w-64 shrink-0 lg:block" aria-label="Product filters">
            @include('livewire.storefront.collections.filters', ['idPrefix' => 'desktop', 'hasActiveFilters' => $this->hasActiveFilters(), 'availableTypes' => $availableTypes, 'availableVendors' => $availableVendors])
        </aside>

        @if ($showMobileFilters)
            <div class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true" aria-label="Product filters">
                <button type="button" wire:click="$set('showMobileFilters', false)" class="absolute inset-0 h-full w-full cursor-default bg-black/50" aria-label="Close filters" tabindex="-1"></button>
                <div class="absolute left-0 top-0 flex h-full w-80 flex-col bg-white p-4 dark:bg-zinc-950">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-lg font-semibold">Filters</h2>
                        <button type="button" wire:click="$set('showMobileFilters', false)" aria-label="Close filters">x</button>
                    </div>
                    <div class="flex-1 overflow-y-auto">
                        @include('livewire.storefront.collections.filters', ['idPrefix' => 'mobile', 'hasActiveFilters' => $this->hasActiveFilters(), 'availableTypes' => $availableTypes, 'availableVendors' => $availableVendors])
                    </div>
                    <flux:button wire:click="$set('showMobileFilters', false)" variant="primary" class="mt-4 w-full">Apply</flux:button>
                </div>
            </div>
        @endif

        <div class="min-w-0 flex-1" wire:loading.class="opacity-50">
            @if ($products->isEmpty())
                <div class="flex flex-col items-center gap-2 py-16 text-center">
                    <flux:icon.magnifying-glass class="size-12 text-zinc-300 dark:text-zinc-700" variant="outline" />
                    <p class="font-semibold">No products found</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Try adjusting your filters or browse our full collection.</p>
                    <flux:button wire:click="clearFilters" variant="ghost">Clear filters</flux:button>
                </div>
            @else
                <div class="grid grid-cols-2 gap-4 md:grid-cols-3" aria-live="polite">
                    @foreach ($products as $product)
                        <x-storefront.product-card :product="$product" wire:key="grid-{{ $product->getKey() }}" />
                    @endforeach
                </div>
                <x-storefront.pagination :paginator="$products" />
            @endif
        </div>
    </div>
</div>
