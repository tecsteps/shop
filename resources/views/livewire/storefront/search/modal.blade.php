<div>
    @if($open)
        {{-- Backdrop --}}
        <div x-data
             x-init="$nextTick(() => $refs.searchInput?.focus())"
             x-on:keydown.escape.window="$wire.closeModal()"
             class="fixed inset-0 z-50"
             role="dialog"
             aria-modal="true"
             aria-label="Search">
            <div class="fixed inset-0 bg-black/50 transition-opacity"
                 wire:click="closeModal"></div>

            {{-- Modal --}}
            <div class="fixed inset-x-0 top-0 z-50 mx-auto mt-20 w-full max-w-xl px-4">
                <div class="rounded-lg bg-white shadow-2xl dark:bg-zinc-900 dark:border dark:border-zinc-700">
                    {{-- Search Input --}}
                    <div class="flex items-center border-b border-zinc-200 dark:border-zinc-700 px-4">
                        <svg class="h-5 w-5 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                        <input x-ref="searchInput"
                               type="text"
                               wire:model.live.debounce.300ms="searchQuery"
                               placeholder="Search products..."
                               class="flex-1 border-0 bg-transparent px-3 py-4 text-base text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:outline-none focus:ring-0">
                        <button wire:click="closeModal"
                                class="p-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300"
                                aria-label="Close search">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- Results --}}
                    @if(strlen($searchQuery) >= 2)
                        <div class="max-h-96 overflow-y-auto px-2 py-2">
                            @if($results->isNotEmpty())
                                <div class="px-2 py-1.5">
                                    <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Products</span>
                                </div>
                                @foreach($results as $product)
                                    @php
                                        $defaultVariant = $product->variants->firstWhere('is_default', true) ?? $product->variants->first();
                                        $price = $defaultVariant?->price_amount ?? 0;
                                        $currency = $defaultVariant?->currency ?? 'USD';
                                        $primaryImage = $product->media?->sortBy('position')->first();
                                    @endphp
                                    <a href="{{ route('storefront.products.show', $product->handle) }}"
                                       class="flex items-center gap-3 rounded-md px-2 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                                        {{-- Thumbnail --}}
                                        <div class="h-10 w-10 flex-shrink-0 overflow-hidden rounded bg-zinc-100 dark:bg-zinc-800">
                                            @if($primaryImage)
                                                <img src="{{ $primaryImage->storage_key }}"
                                                     alt="{{ $primaryImage->alt_text ?? $product->title }}"
                                                     class="h-full w-full object-cover">
                                            @else
                                                <div class="flex h-full w-full items-center justify-center">
                                                    <svg class="h-5 w-5 text-zinc-300 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                                    </svg>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-zinc-900 dark:text-white truncate">{{ $product->title }}</p>
                                        </div>
                                        <span class="text-sm text-zinc-600 dark:text-zinc-400">
                                            ${{ number_format($price / 100, 2) }}
                                        </span>
                                    </a>
                                @endforeach

                                {{-- View All Link --}}
                                <div class="border-t border-zinc-200 dark:border-zinc-700 mt-2 pt-2 px-2">
                                    <a href="{{ route('storefront.search', ['q' => $searchQuery]) }}"
                                       class="block text-center text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 py-2">
                                        View all results for "{{ $searchQuery }}"
                                    </a>
                                </div>
                            @else
                                <div class="px-2 py-8 text-center">
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">No results for "{{ $searchQuery }}"</p>
                                </div>
                            @endif
                        </div>
                    @elseif(strlen($searchQuery) > 0)
                        <div class="px-2 py-4 text-center">
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Type at least 2 characters to search</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
