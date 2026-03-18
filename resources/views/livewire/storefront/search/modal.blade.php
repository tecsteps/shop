<div>
    @if($isOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            {{-- Backdrop --}}
            <div class="fixed inset-0 bg-zinc-900/50 transition-opacity" wire:click="close"></div>

            {{-- Modal content --}}
            <div class="relative mx-auto mt-20 max-w-lg px-4">
                <div class="rounded-xl bg-white shadow-2xl ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-zinc-700">
                    {{-- Search input --}}
                    <div class="flex items-center border-b border-zinc-200 px-4 dark:border-zinc-700">
                        <svg class="h-5 w-5 shrink-0 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                        <input type="search"
                               wire:model.live.debounce.200ms="query"
                               wire:keydown.arrow-up.prevent="navigateUp"
                               wire:keydown.arrow-down.prevent="navigateDown"
                               wire:keydown.enter.prevent="selectCurrent"
                               wire:keydown.escape="close"
                               placeholder="Search products..."
                               class="w-full border-0 bg-transparent px-3 py-4 text-sm text-zinc-900 placeholder-zinc-400 focus:outline-none focus:ring-0 dark:text-white dark:placeholder-zinc-500"
                               autofocus>
                        <button wire:click="close" class="shrink-0 text-xs text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                            ESC
                        </button>
                    </div>

                    {{-- Results --}}
                    @if(trim($query) !== '')
                        <div class="max-h-80 overflow-y-auto px-2 py-2">
                            @forelse($this->suggestions as $index => $product)
                                <a href="{{ route('storefront.products.show', $product->handle) }}"
                                   wire:navigate
                                   wire:click="close"
                                   class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition {{ $selectedIndex === $index ? 'bg-blue-50 dark:bg-blue-900/30' : 'hover:bg-zinc-50 dark:hover:bg-zinc-800' }}">
                                    @if($product->media->first())
                                        <div class="h-10 w-10 shrink-0 overflow-hidden rounded bg-zinc-100 dark:bg-zinc-800">
                                            <img src="{{ asset('storage/' . $product->media->first()->storage_key) }}"
                                                 alt="{{ $product->media->first()->alt_text }}"
                                                 class="h-full w-full object-cover">
                                        </div>
                                    @else
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded bg-zinc-100 dark:bg-zinc-800">
                                            <svg class="h-5 w-5 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" />
                                            </svg>
                                        </div>
                                    @endif
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate font-medium text-zinc-900 dark:text-white">{{ $product->title }}</p>
                                        @if($product->variants->first())
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                                {{ number_format($product->variants->first()->price_amount / 100, 2) }} {{ $product->variants->first()->currency }}
                                            </p>
                                        @endif
                                    </div>
                                </a>
                            @empty
                                <div class="px-3 py-6 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                    No products found for "{{ $query }}"
                                </div>
                            @endforelse

                            @if($this->suggestions->isNotEmpty())
                                <div class="border-t border-zinc-100 px-3 py-2 dark:border-zinc-800">
                                    <a href="{{ route('storefront.search', ['q' => $query]) }}"
                                       wire:navigate
                                       wire:click="close"
                                       class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                        View all results for "{{ $query }}"
                                    </a>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="px-3 py-6 text-center text-sm text-zinc-500 dark:text-zinc-400">
                            Start typing to search...
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
