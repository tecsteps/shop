<div>
    <div x-data="{ open: @entangle('open') }"
         x-show="open"
         x-cloak
         class="fixed inset-0 z-50"
         role="dialog"
         aria-modal="true"
         aria-label="Search">
        {{-- Overlay --}}
        <div x-show="open"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="open = false"
             class="fixed inset-0 bg-black/50"></div>

        {{-- Modal --}}
        <div x-show="open"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-4"
             x-trap.noscroll="open"
             @keydown.escape.window="open = false"
             class="fixed inset-x-0 top-0 mx-auto max-w-2xl p-4 pt-16 sm:pt-24">
            <div class="overflow-hidden rounded-xl bg-white shadow-2xl dark:bg-gray-800">
                <form action="{{ route('storefront.search') }}" method="GET" class="relative">
                    <svg class="pointer-events-none absolute left-4 top-3.5 h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    <input type="search"
                           name="q"
                           wire:model.live.debounce.300ms="query"
                           placeholder="Search products..."
                           class="w-full border-0 bg-transparent py-3 pl-12 pr-4 text-gray-900 placeholder:text-gray-400 focus:ring-0 dark:text-white dark:placeholder:text-gray-500 sm:text-sm"
                           x-ref="searchInput"
                           @focus="$nextTick(() => $refs.searchInput.select())"
                           autocomplete="off">
                </form>
                {{-- Autocomplete results will be populated once Phase 8 is complete --}}
            </div>
        </div>
    </div>
</div>
