<div
    x-data="{
        open: false,
        active: -1,
        options() { return Array.from($el.querySelectorAll('[data-search-option]')) },
        move(step) {
            const items = this.options()
            if (items.length === 0) return
            this.active = (this.active + step + items.length) % items.length
            items.forEach((item, index) => item.classList.toggle('bg-zinc-100', index === this.active))
            items.forEach((item, index) => item.classList.toggle('dark:bg-zinc-800', index === this.active))
            items[this.active]?.scrollIntoView({ block: 'nearest' })
        },
        select() {
            const item = this.options()[this.active]
            if (item) { item.click() }
        },
        show() {
            this.open = true
            this.active = -1
            $nextTick(() => $refs.searchInput?.focus())
        },
        hide() { this.open = false },
    }"
    x-on:open-search-modal.window="show()"
    x-on:close-search-modal.window="hide()"
    x-on:keydown.escape.window="hide()"
>
    <div
        x-show="open"
        x-cloak
        class="relative z-50"
        role="dialog"
        aria-modal="true"
        aria-label="{{ __('Search') }}"
    >
        {{-- Backdrop --}}
        <div
            x-show="open"
            x-transition.opacity.duration.200ms
            x-on:click="hide()"
            class="fixed inset-0 bg-zinc-950/50"
            aria-hidden="true"
        ></div>

        {{-- Modal panel --}}
        <div class="fixed inset-x-0 top-0 flex justify-center p-4 pt-[10vh] sm:p-6 sm:pt-[12vh]">
            <div
                x-show="open"
                x-transition:enter="transition duration-200 ease-out"
                x-transition:enter-start="scale-95 opacity-0"
                x-transition:enter-end="scale-100 opacity-100"
                x-on:keydown.arrow-down.prevent="move(1)"
                x-on:keydown.arrow-up.prevent="move(-1)"
                x-on:keydown.enter.prevent="select()"
                class="flex max-h-[70vh] w-full max-w-xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl dark:bg-zinc-900"
            >
                {{-- Search input --}}
                <div class="flex items-center gap-3 border-b border-zinc-200 px-4 py-3 dark:border-zinc-800">
                    <svg class="size-5 shrink-0 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    <input
                        type="search"
                        x-ref="searchInput"
                        wire:model.live.debounce.300ms="query"
                        x-on:input="active = -1"
                        placeholder="{{ __('Search products...') }}"
                        aria-label="{{ __('Search products') }}"
                        class="w-full border-0 bg-transparent text-base text-zinc-900 placeholder-zinc-400 focus:outline-none focus:ring-0 dark:text-white"
                    />
                    <button
                        type="button"
                        x-on:click="hide()"
                        class="rounded-lg p-1.5 text-zinc-500 transition hover:bg-zinc-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:text-zinc-400 dark:hover:bg-zinc-800"
                        aria-label="{{ __('Close search') }}"
                    >
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Results --}}
                <div class="flex-1 overflow-y-auto" role="listbox" aria-label="{{ __('Search results') }}">
                    {{-- Loading skeleton --}}
                    <div wire:loading wire:target="query" class="space-y-3 px-4 py-4" aria-hidden="true">
                        @foreach (range(1, 3) as $skeleton)
                            <div class="flex animate-pulse items-center gap-3">
                                <div class="size-10 rounded-lg bg-zinc-200 dark:bg-zinc-800"></div>
                                <div class="h-3 w-2/3 rounded bg-zinc-200 dark:bg-zinc-800"></div>
                            </div>
                        @endforeach
                    </div>

                    <div wire:loading.remove wire:target="query">
                        @if ($hasQuery)
                            @if ($products === [] && $collections === [])
                                <p class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ __('No results for ":query"', ['query' => $query]) }}
                                </p>
                            @else
                                @if ($products !== [])
                                    <p class="px-4 pt-4 pb-1 text-xs font-semibold tracking-wide text-zinc-400 uppercase dark:text-zinc-500">
                                        {{ __('Products') }}
                                    </p>
                                    @foreach ($products as $product)
                                        <a
                                            href="{{ route('storefront.products.show', $product['handle']) }}"
                                            data-search-option
                                            role="option"
                                            wire:key="search-product-{{ $product['handle'] }}"
                                            class="flex items-center gap-3 px-4 py-2.5 transition hover:bg-zinc-100 focus:bg-zinc-100 focus:outline-none dark:hover:bg-zinc-800 dark:focus:bg-zinc-800"
                                        >
                                            @if ($product['image_url'] !== null)
                                                <img src="{{ $product['image_url'] }}" alt="" class="size-10 rounded-lg object-cover" />
                                            @else
                                                <div class="flex size-10 items-center justify-center rounded-lg bg-zinc-100 text-zinc-400 dark:bg-zinc-800" aria-hidden="true">
                                                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A1.5 1.5 0 0 0 21.75 19.5V4.5A1.5 1.5 0 0 0 20.25 3H3.75A1.5 1.5 0 0 0 2.25 4.5v15A1.5 1.5 0 0 0 3.75 21Z" /></svg>
                                                </div>
                                            @endif
                                            <span class="flex-1 truncate text-sm font-medium text-zinc-900 dark:text-white">{{ $product['title'] }}</span>
                                            <span class="text-sm text-zinc-500 dark:text-zinc-400">
                                                {{ \App\Support\Storefront\PriceFormatter::format($product['price_amount'], $product['currency']) }}
                                            </span>
                                        </a>
                                    @endforeach
                                @endif

                                @if ($collections !== [])
                                    <p class="px-4 pt-4 pb-1 text-xs font-semibold tracking-wide text-zinc-400 uppercase dark:text-zinc-500">
                                        {{ __('Collections') }}
                                    </p>
                                    @foreach ($collections as $collection)
                                        <a
                                            href="{{ route('storefront.collections.show', $collection['handle']) }}"
                                            data-search-option
                                            role="option"
                                            wire:key="search-collection-{{ $collection['handle'] }}"
                                            class="block px-4 py-2.5 text-sm font-medium text-zinc-900 transition hover:bg-zinc-100 focus:bg-zinc-100 focus:outline-none dark:text-white dark:hover:bg-zinc-800 dark:focus:bg-zinc-800"
                                        >
                                            {{ $collection['title'] }}
                                        </a>
                                    @endforeach
                                @endif

                                @if ($totalResults > 0)
                                    <div class="border-t border-zinc-200 dark:border-zinc-800">
                                        <a
                                            href="{{ route('storefront.search', ['q' => $query]) }}"
                                            data-search-option
                                            role="option"
                                            class="flex items-center justify-between px-4 py-3 text-sm font-semibold text-blue-700 transition hover:bg-zinc-100 focus:bg-zinc-100 focus:outline-none dark:text-blue-400 dark:hover:bg-zinc-800 dark:focus:bg-zinc-800"
                                        >
                                            {{ trans_choice('View all :count result|View all :count results', $totalResults, ['count' => $totalResults]) }}
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                            </svg>
                                        </a>
                                    </div>
                                @endif
                            @endif
                        @else
                            <p class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('Start typing to search products and collections.') }}
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
