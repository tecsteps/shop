<div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    <x-storefront::breadcrumbs :items="[
        ['label' => __('Home'), 'url' => route('storefront.home')],
        ['label' => __('Search results')],
    ]" />

    <h1 class="mt-4 text-2xl font-bold tracking-tight text-zinc-900 dark:text-white sm:text-3xl">
        @if (strlen(trim($query)) > 0)
            {{ trans_choice(':total result for \':query\'|:total results for \':query\'', $total, ['total' => $total, 'query' => $query]) }}
        @else
            {{ __('Search') }}
        @endif
    </h1>

    <form action="{{ route('storefront.search') }}" method="GET" class="mt-6 flex max-w-md gap-2">
        <input type="search" name="q" value="{{ $query }}" placeholder="{{ __('Search') }}"
               class="flex-1 rounded-lg border border-zinc-300 bg-white px-4 py-2 text-zinc-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white" />
        <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 font-medium text-white transition hover:bg-blue-700">
            {{ __('Search') }}
        </button>
    </form>

    @if ($results->isNotEmpty())
        <div class="mt-6 flex items-center justify-end border-b border-zinc-200 pb-4 dark:border-zinc-800">
            <label for="search-sort" class="sr-only">{{ __('Sort by') }}</label>
            <select id="search-sort" wire:model.live="sort"
                    class="rounded-lg border border-zinc-300 bg-white py-1.5 pl-3 pr-8 text-sm text-zinc-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">
                <option value="relevance">{{ __('Relevance') }}</option>
                <option value="price_asc">{{ __('Price: Low to High') }}</option>
                <option value="price_desc">{{ __('Price: High to Low') }}</option>
                <option value="newest">{{ __('Newest') }}</option>
                <option value="best_selling">{{ __('Best Selling') }}</option>
            </select>
        </div>
    @endif

    @if ($results->isEmpty())
        <div class="py-20 text-center">
            <flux:icon.magnifying-glass class="mx-auto size-12 text-zinc-300 dark:text-zinc-600" />
            <h2 class="mt-4 text-lg font-semibold text-zinc-900 dark:text-white">
                @if (strlen($query) > 0)
                    {{ __("No results found for ':query'.", ['query' => $query]) }}
                @else
                    {{ __('Start typing to search.') }}
                @endif
            </h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Try a different search term.') }}</p>
        </div>
    @else
        <div class="mt-8 grid grid-cols-2 gap-x-4 gap-y-8 md:grid-cols-3 lg:grid-cols-4" wire:loading.class="opacity-50">
            @foreach ($results as $card)
                <x-storefront::product-card :product="$card" />
            @endforeach
        </div>

        @if ($paginator !== null)
            <div class="mt-10">
                <x-storefront::pagination :paginator="$paginator" />
            </div>
        @endif
    @endif
</div>
