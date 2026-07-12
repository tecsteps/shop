<div class="sf-container sf-page-y" x-data="{ mobileFilters: @entangle('filtersOpen').live }">
    <x-storefront.breadcrumbs :items="[['label' => 'Home', 'url' => url('/')], ['label' => 'Search results']]" />
    <header class="mt-7">
        <h1 class="sf-page-title">{{ $query !== '' ? $results->total().' results for “'.$query.'”' : 'Search products' }}</h1>
        <form wire:submit="searchNow" class="mt-6 flex max-w-2xl gap-2"><label class="min-w-0 flex-1"><span class="sr-only">Search products</span><input type="search" name="q" wire:model="query" class="sf-input min-h-12 w-full" placeholder="What are you looking for?" aria-label="Search products"></label><button class="sf-button sf-button-primary min-h-12">Search</button></form>
        @error('query')<p class="sf-field-error">{{ $message }}</p>@enderror
    </header>

    @if ($query !== '')
        <div class="mt-9 flex items-center justify-between border-y border-slate-200 py-4 dark:border-slate-800"><button type="button" @click="mobileFilters = true" class="sf-button sf-button-secondary lg:hidden">Filters</button><p class="hidden text-sm text-slate-500 sm:block" aria-live="polite">{{ $results->total() }} results</p><label class="ml-auto flex items-center gap-3 text-sm font-medium"><span class="hidden sm:inline">Sort by</span><select wire:model.live="sort" class="sf-select min-w-44" aria-label="Sort search results"><option value="relevance">Relevance</option><option value="price_asc">Price: Low to High</option><option value="price_desc">Price: High to Low</option><option value="newest">Newest</option><option value="best_selling">Best Selling</option></select></label></div>
        <div class="mt-8 lg:grid lg:grid-cols-[16rem_minmax(0,1fr)] lg:gap-9">
            <div x-show="mobileFilters" x-cloak class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true" aria-label="Search filters" @keydown.escape.window="mobileFilters = false"><button class="absolute inset-0 bg-slate-950/50" @click="mobileFilters = false" aria-label="Close filters"></button><div class="absolute inset-y-0 left-0 flex w-full max-w-sm flex-col bg-white p-5 shadow-2xl dark:bg-slate-950"><div class="mb-5 flex items-center justify-between"><h2 class="text-lg font-semibold">Filters</h2><button @click="mobileFilters = false" class="sf-icon-button" aria-label="Close filters">&times;</button></div><div class="flex-1 overflow-y-auto">@include('storefront.search._filters')</div><button @click="mobileFilters = false" class="sf-button sf-button-primary mt-5">Show {{ $results->total() }} results</button></div></div>
            <aside class="hidden lg:block" aria-label="Search filters">@include('storefront.search._filters')</aside>
            <section aria-label="Search results" aria-live="polite" wire:loading.class="opacity-50">
                @if ($results->isEmpty())
                    <div class="sf-empty-state"><svg aria-hidden="true" class="mx-auto size-12 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="11" cy="11" r="7" stroke-width="1.5"/><path d="m20 20-4-4" stroke-width="1.5"/></svg><h2 class="mt-4 text-xl font-semibold">No results found for “{{ $query }}”</h2><p class="mt-2 text-slate-500">Try a different search term or clear your filters.</p>@if($this->hasActiveFilters)<button wire:click="clearFilters" class="sf-button sf-button-secondary mt-5">Clear filters</button>@endif</div>
                @else
                    <div class="grid grid-cols-2 gap-x-3 gap-y-8 sm:gap-x-5 md:grid-cols-3">@foreach($results as $product)<x-storefront.product-card :product="$product" />@endforeach</div><div class="mt-12"><x-storefront.pagination :paginator="$results" /></div>
                @endif
            </section>
        </div>
    @endif
</div>
