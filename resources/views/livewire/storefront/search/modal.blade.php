<div x-data="{ open: false }"
     x-on:open-search.window="open = true; $nextTick(() => $refs.searchInput.focus())"
     x-on:keydown.meta.k.window.prevent="open = !open"
     x-on:keydown.ctrl.k.window.prevent="open = !open"
     x-on:keydown.escape.window="open = false">

    {{-- Backdrop --}}
    <div x-show="open"
         x-transition:enter="transition-opacity duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         x-cloak
         @click="open = false"
         class="fixed inset-0 z-50 bg-black/50"></div>

    {{-- Modal --}}
    <div x-show="open"
         x-transition:enter="transition duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         x-cloak
         class="fixed inset-x-0 top-24 z-50 mx-auto w-full max-w-lg px-4"
         role="dialog"
         aria-modal="true"
         aria-label="Search products">
        <div class="overflow-hidden rounded-xl bg-white shadow-2xl ring-1 ring-gray-200 dark:bg-gray-900 dark:ring-gray-700">
            {{-- Search input --}}
            <div class="flex items-center border-b border-gray-200 px-4 dark:border-gray-700">
                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input x-ref="searchInput"
                       type="text"
                       wire:model.live.debounce.300ms="query"
                       wire:keydown.enter="goToSearch"
                       placeholder="Search products..."
                       class="w-full border-0 bg-transparent px-3 py-4 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-0 dark:text-white dark:placeholder-gray-500" />
                <kbd class="hidden rounded border border-gray-300 px-1.5 py-0.5 text-xs text-gray-400 sm:inline-block dark:border-gray-600">esc</kbd>
            </div>

            {{-- Suggestions --}}
            @if($suggestions->isNotEmpty())
                <ul class="max-h-72 overflow-y-auto py-2">
                    @foreach($suggestions as $suggestion)
                        <li>
                            <a href="/products/{{ $suggestion->handle }}"
                               @click="open = false"
                               class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800">
                                <svg class="h-4 w-4 flex-shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                </svg>
                                <span>{{ $suggestion->title }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @elseif(mb_strlen($query) >= 2)
                <div class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                    No products found for "{{ $query }}"
                </div>
            @endif

            {{-- Footer --}}
            @if(mb_strlen($query) >= 2)
                <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700">
                    <a href="{{ route('storefront.search', ['q' => $query]) }}"
                       @click="open = false"
                       class="text-sm text-blue-600 hover:underline dark:text-blue-400">
                        View all results for "{{ $query }}"
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
