@php
    $store = app()->bound('current_store') ? app('current_store') : null;
    $currency = $store?->default_currency ?? 'EUR';
@endphp

<div>
    @if($open)
        {{-- Backdrop --}}
        <div x-data
             x-init="$nextTick(() => $refs.searchInput?.focus())"
             @keydown.escape.window="$wire.closeModal()"
             class="fixed inset-0 z-50 overflow-y-auto"
             role="dialog"
             aria-modal="true"
             aria-label="Search">

            <div class="fixed inset-0 bg-black/50 transition-opacity" @click="$wire.closeModal()"></div>

            {{-- Modal --}}
            <div class="relative mx-auto mt-20 max-w-2xl px-4 sm:px-6">
                <div class="rounded-xl bg-white shadow-2xl dark:bg-gray-900">
                    {{-- Search Input --}}
                    <div class="flex items-center border-b border-gray-200 px-4 dark:border-gray-800">
                        <svg class="h-5 w-5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                        <input x-ref="searchInput"
                               wire:model.live.debounce.300ms="query"
                               type="text"
                               placeholder="Search products..."
                               class="w-full border-0 bg-transparent px-3 py-4 text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-0 dark:text-white dark:placeholder-gray-500">
                        <button @click="$wire.closeModal()"
                                class="shrink-0 p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                aria-label="Close search">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- Results --}}
                    <div class="max-h-[60vh] overflow-y-auto p-4">
                        {{-- Loading --}}
                        <div wire:loading wire:target="query" class="space-y-3">
                            @for($i = 0; $i < 3; $i++)
                                <div class="flex animate-pulse items-center gap-3">
                                    <div class="h-12 w-12 rounded-md bg-gray-200 dark:bg-gray-800"></div>
                                    <div class="flex-1">
                                        <div class="h-4 w-3/4 rounded bg-gray-200 dark:bg-gray-800"></div>
                                        <div class="mt-2 h-3 w-1/4 rounded bg-gray-200 dark:bg-gray-800"></div>
                                    </div>
                                </div>
                            @endfor
                        </div>

                        <div wire:loading.remove wire:target="query">
                            @if($hasSearched && empty($productResults) && empty($collectionResults))
                                {{-- No Results --}}
                                <p class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                    No results for "{{ $query }}"
                                </p>
                            @endif

                            @if(count($productResults) > 0)
                                {{-- Product Results --}}
                                <div>
                                    <h3 class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        Products
                                    </h3>
                                    <ul class="space-y-1">
                                        @foreach($productResults as $product)
                                            <li wire:key="search-product-{{ $product['id'] }}">
                                                <a href="{{ route('storefront.products.show', $product['handle']) }}"
                                                   class="flex items-center gap-3 rounded-lg px-2 py-2 transition-colors hover:bg-gray-100 dark:hover:bg-gray-800">
                                                    @if($product['image'])
                                                        <img src="{{ $product['image'] }}"
                                                             alt="{{ $product['title'] }}"
                                                             class="h-12 w-12 rounded-md object-cover">
                                                    @else
                                                        <div class="flex h-12 w-12 items-center justify-center rounded-md bg-gray-100 dark:bg-gray-800">
                                                            <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" />
                                                            </svg>
                                                        </div>
                                                    @endif
                                                    <div class="min-w-0 flex-1">
                                                        <p class="truncate text-sm font-medium text-gray-900 dark:text-white">
                                                            {{ $product['title'] }}
                                                        </p>
                                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                                            <x-storefront.price :amount="$product['price']" :currency="$currency" />
                                                        </p>
                                                    </div>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if(count($collectionResults) > 0)
                                {{-- Collection Results --}}
                                <div class="{{ count($productResults) > 0 ? 'mt-4 border-t border-gray-200 pt-4 dark:border-gray-800' : '' }}">
                                    <h3 class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        Collections
                                    </h3>
                                    <ul class="space-y-1">
                                        @foreach($collectionResults as $collection)
                                            <li wire:key="search-collection-{{ $collection['id'] }}">
                                                <a href="{{ route('storefront.collections.show', $collection['handle']) }}"
                                                   class="flex items-center gap-3 rounded-lg px-2 py-2 transition-colors hover:bg-gray-100 dark:hover:bg-gray-800">
                                                    <svg class="h-5 w-5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 7.125C2.25 6.504 2.754 6 3.375 6h6c.621 0 1.125.504 1.125 1.125v3.75c0 .621-.504 1.125-1.125 1.125h-6a1.125 1.125 0 0 1-1.125-1.125v-3.75ZM14.25 8.625c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125v8.25c0 .621-.504 1.125-1.125 1.125h-5.25a1.125 1.125 0 0 1-1.125-1.125v-8.25ZM3.75 16.125c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125v2.25c0 .621-.504 1.125-1.125 1.125h-5.25a1.125 1.125 0 0 1-1.125-1.125v-2.25Z" />
                                                    </svg>
                                                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                                                        {{ $collection['title'] }}
                                                    </span>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if($hasSearched && (count($productResults) > 0 || count($collectionResults) > 0))
                                {{-- View All Results --}}
                                <div class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-800">
                                    <a href="{{ route('storefront.search', ['q' => $query]) }}"
                                       class="flex items-center justify-center gap-1 text-sm font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300">
                                        View all results
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                        </svg>
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
