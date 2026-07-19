{{-- Search-as-you-type modal (spec 04 §11.1). Opened via the header search icon. --}}
<div x-data="{
        open: false,
        move(step) {
            const options = Array.from(this.$el.querySelectorAll('[data-search-option]'));
            if (options.length === 0) return;
            const current = options.indexOf(document.activeElement);
            const next = (current + step + options.length) % options.length;
            options[next].focus();
        },
     }"
     @open-search-modal.window="open = true; $nextTick(() => $refs.searchInput?.focus())"
     @keydown.escape.window="open = false"
     @keydown.arrow-down.prevent="move(1)"
     @keydown.arrow-up.prevent="move(-1)">
    <div x-show="open" x-cloak role="dialog" aria-modal="true" aria-label="Search">
        <div x-show="open"
             x-transition:enter="transition-opacity ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             @click="open = false"
             class="fixed inset-0 z-40 bg-gray-900/50 dark:bg-black/60" aria-hidden="true"></div>

        <div x-show="open"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2"
             class="fixed inset-x-0 top-16 z-50 mx-auto w-full max-w-xl px-4">
            <div class="overflow-hidden rounded-xl bg-white shadow-xl ring-1 ring-gray-900/5 dark:bg-gray-950 dark:ring-white/10">
                {{-- Input row --}}
                <form wire:submit="search" class="flex items-center gap-3 border-b border-gray-200 px-4 dark:border-gray-800" role="search">
                    <svg class="size-5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                    <label for="storefront-search-input" class="sr-only">Search products</label>
                    <input id="storefront-search-input"
                           x-ref="searchInput"
                           type="search"
                           wire:model.live.debounce.300ms="query"
                           placeholder="Search products..."
                           autocomplete="off"
                           role="combobox"
                           aria-autocomplete="list"
                           aria-controls="search-suggestions"
                           aria-expanded="{{ $suggestions->isNotEmpty() ? 'true' : 'false' }}"
                           class="w-full border-0 bg-transparent py-3.5 text-base text-gray-900 placeholder-gray-400 focus:outline-hidden focus:ring-0 dark:text-white">
                    <button type="submit" class="sr-only">Search</button>
                    <button type="button"
                            @click="open = false"
                            class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 focus:outline-hidden focus:ring-2 focus:ring-blue-500 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                            aria-label="Close search">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </form>

                {{-- Loading skeleton --}}
                <div wire:loading.block wire:target="query" class="space-y-3 p-4" aria-hidden="true">
                    <div class="h-4 w-1/3 animate-pulse rounded bg-gray-200 dark:bg-gray-800"></div>
                    <div class="h-12 animate-pulse rounded bg-gray-100 dark:bg-gray-800/60"></div>
                    <div class="h-12 animate-pulse rounded bg-gray-100 dark:bg-gray-800/60"></div>
                    <div class="h-12 animate-pulse rounded bg-gray-100 dark:bg-gray-800/60"></div>
                </div>

                @if (trim($query) !== '')
                    <div wire:loading.remove wire:target="query">
                        @if ($suggestions->isEmpty())
                            <p class="px-4 py-6 text-sm text-gray-500 dark:text-gray-400">No results for &ldquo;{{ $query }}&rdquo;</p>
                        @else
                            <ul id="search-suggestions" role="listbox" aria-label="Search suggestions" class="max-h-96 overflow-y-auto py-2">
                                @if ($products->isNotEmpty())
                                    <li class="px-4 pt-2 pb-1 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400" aria-hidden="true">Products</li>
                                    @foreach ($products as $suggestion)
                                        <li role="option" aria-selected="false" wire:key="suggest-product-{{ $suggestion['handle'] }}">
                                            <a href="{{ route('storefront.products.show', ['handle' => $suggestion['handle']]) }}"
                                               data-search-option
                                               class="flex items-center gap-3 px-4 py-2 hover:bg-gray-50 focus:bg-gray-50 focus:outline-hidden dark:hover:bg-gray-800 dark:focus:bg-gray-800">
                                                @if ($suggestion['image_url'])
                                                    <img src="{{ $suggestion['image_url'] }}" alt="" class="size-10 shrink-0 rounded-md object-cover">
                                                @else
                                                    <span class="flex size-10 shrink-0 items-center justify-center rounded-md bg-gray-100 text-gray-300 dark:bg-gray-800 dark:text-gray-600">
                                                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z" />
                                                        </svg>
                                                    </span>
                                                @endif
                                                <span class="min-w-0 flex-1 truncate text-sm font-medium text-gray-900 dark:text-white">{{ $suggestion['title'] }}</span>
                                                @if ($suggestion['price_amount'] !== null)
                                                    <span class="shrink-0 text-sm text-gray-600 dark:text-gray-300">
                                                        <x-storefront::price :amount="$suggestion['price_amount']" :currency="$suggestion['currency']" />
                                                    </span>
                                                @endif
                                            </a>
                                        </li>
                                    @endforeach
                                @endif

                                @if ($collections->isNotEmpty())
                                    <li class="px-4 pt-2 pb-1 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400" aria-hidden="true">Collections</li>
                                    @foreach ($collections as $suggestion)
                                        <li role="option" aria-selected="false" wire:key="suggest-collection-{{ $suggestion['handle'] }}">
                                            <a href="{{ route('storefront.collections.show', ['handle' => $suggestion['handle']]) }}"
                                               data-search-option
                                               class="block px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-50 focus:bg-gray-50 focus:outline-hidden dark:text-white dark:hover:bg-gray-800 dark:focus:bg-gray-800">
                                                {{ $suggestion['title'] }}
                                            </a>
                                        </li>
                                    @endforeach
                                @endif

                                @if ($pastQueries->isNotEmpty())
                                    <li class="px-4 pt-2 pb-1 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400" aria-hidden="true">Popular searches</li>
                                    @foreach ($pastQueries as $suggestion)
                                        <li role="option" aria-selected="false" wire:key="suggest-query-{{ md5($suggestion['title']) }}">
                                            <a href="{{ route('storefront.search', ['q' => $suggestion['title']]) }}"
                                               data-search-option
                                               class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 focus:bg-gray-50 focus:outline-hidden dark:text-gray-300 dark:hover:bg-gray-800 dark:focus:bg-gray-800">
                                                <svg class="size-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                                                </svg>
                                                {{ $suggestion['title'] }}
                                            </a>
                                        </li>
                                    @endforeach
                                @endif
                            </ul>

                            <a href="{{ route('storefront.search', ['q' => $query]) }}"
                               class="block border-t border-gray-200 px-4 py-3 text-sm font-semibold text-blue-600 hover:bg-gray-50 focus:outline-hidden focus:ring-2 focus:ring-inset focus:ring-blue-500 dark:border-gray-800 dark:text-blue-400 dark:hover:bg-gray-800">
                                View all results for &ldquo;{{ $query }}&rdquo; &rarr;
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
