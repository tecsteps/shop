<div
    class="fixed inset-0 z-[60]"
    wire:key="search-modal"
    x-data="{
        highlight: 0,
        moveHighlight(dir) {
            const links = this.\$refs.results?.querySelectorAll('a[data-search-result]');
            if (! links || links.length === 0) return;
            this.highlight = Math.min(Math.max(this.highlight + dir, 0), links.length - 1);
            links[this.highlight].focus();
        },
        resetHighlight() {
            this.highlight = 0;
        },
    }"
    x-effect="
        if (\$wire.open) {
            document.body.classList.add('overflow-hidden');
            \$nextTick(() => \$refs.input?.focus());
        } else {
            document.body.classList.remove('overflow-hidden');
        }
    "
    @keydown.escape.window="$wire.closeModal()"
>
    {{-- Backdrop --}}
    <div
        x-show="$wire.open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="absolute inset-0 bg-zinc-950/50 backdrop-blur-sm"
        @click="$wire.closeModal()"
        aria-hidden="true"
    ></div>

    {{-- Modal --}}
    <div
        x-show="$wire.open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute inset-x-0 top-0 mx-auto mt-20 w-[calc(100%-2rem)] max-w-xl rounded-2xl bg-white shadow-2xl dark:bg-zinc-950 sm:mt-24"
        role="dialog"
        aria-modal="true"
        aria-label="Search"
    >
        {{-- Input --}}
        <div class="flex items-center gap-3 border-b border-zinc-200 px-5 py-4 dark:border-zinc-800">
            <svg class="size-5 shrink-0 text-zinc-400 dark:text-zinc-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="8" />
                <path d="m21 21-4.35-4.35" />
            </svg>
            <label for="search-modal-input" class="sr-only">Search products</label>
            <input
                id="search-modal-input"
                x-ref="input"
                type="search"
                wire:model.live.debounce.300ms="query"
                @input="resetHighlight()"
                placeholder="Search products..."
                autocomplete="off"
                class="w-full bg-transparent text-base text-zinc-900 placeholder-zinc-400 focus:outline-none dark:text-white dark:placeholder-zinc-500"
            />
            <button
                type="button"
                @click="$wire.closeModal()"
                aria-label="Close search"
                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-900 dark:hover:bg-zinc-800 dark:hover:text-white"
            >
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M18 6 6 18M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Results --}}
        <div
            x-ref="results"
            class="max-h-[60vh] overflow-y-auto p-2"
            @keydown.arrow-down.prevent="moveHighlight(1)"
            @keydown.arrow-up.prevent="moveHighlight(-1)"
        >
            {{-- Loading skeleton --}}
            <div wire:loading wire:target="query" class="space-y-2 p-3">
                @for ($i = 0; $i < 4; $i++)
                    <div class="h-10 animate-pulse rounded-lg bg-zinc-100 dark:bg-zinc-800"></div>
                @endfor
            </div>

            <div wire:loading.remove wire:target="query">
                @if (trim($this->query) === '')
                    <p class="px-3 py-6 text-center text-sm text-zinc-500 dark:text-zinc-400">
                        Start typing to search products and collections.
                    </p>
                @elseif (! $this->hasResults)
                    <p class="px-3 py-6 text-center text-sm text-zinc-500 dark:text-zinc-400">
                        No results for &ldquo;{{ $this->query }}&rdquo;
                    </p>
                @else
                    @if ($this->productResults->isNotEmpty())
                        <p class="px-3 pb-1 pt-3 text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Products</p>
                        <ul role="listbox" aria-label="Product results">
                            @foreach ($this->productResults as $product)
                                <li role="option" aria-selected="false">
                                    <a
                                        href="{{ route('storefront.product', ['handle' => $product->handle]) }}"
                                        data-search-result
                                        @click="$wire.closeModal()"
                                        class="flex items-center gap-3 rounded-lg px-3 py-2.5 transition hover:bg-zinc-100 focus:bg-zinc-100 focus:outline-none dark:hover:bg-zinc-800 dark:focus:bg-zinc-800"
                                    >
                                        <span class="shrink-0 overflow-hidden rounded-md bg-zinc-100 dark:bg-zinc-800">
                                            @php
                                                $image = $product->media->where('type', 'image')->first();
                                            @endphp
                                            @if ($image)
                                                <img src="{{ Storage::url($image->storage_key) }}" alt="" class="size-9 object-cover" />
                                            @else
                                                <span class="flex size-9 items-center justify-center text-zinc-300 dark:text-zinc-600">
                                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" /><path d="M3 6h18" /><path d="M16 10a4 4 0 0 1-8 0" /></svg>
                                                </span>
                                            @endif
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-sm font-medium text-zinc-900 dark:text-white">{{ $product->title }}</span>
                                        </span>
                                        @php
                                            $price = $product->variants->where('status', 'active')->min('price_amount') ?? 0;
                                        @endphp
                                        <x-storefront-price :amount="$price" :currency="$product->variants->first()?->currency ?? (app()->bound('current_store') ? app('current_store')->default_currency : 'EUR')" class="text-sm" />
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if ($this->collectionResults->isNotEmpty())
                        <p class="px-3 pb-1 pt-4 text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Collections</p>
                        <ul role="listbox" aria-label="Collection results">
                            @foreach ($this->collectionResults as $collection)
                                <li role="option" aria-selected="false">
                                    <a
                                        href="{{ route('storefront.collection', ['handle' => $collection->handle]) }}"
                                        data-search-result
                                        @click="$wire.closeModal()"
                                        class="flex items-center gap-3 rounded-lg px-3 py-2.5 transition hover:bg-zinc-100 focus:bg-zinc-100 focus:outline-none dark:hover:bg-zinc-800 dark:focus:bg-zinc-800"
                                    >
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-sm font-medium text-zinc-900 dark:text-white">{{ $collection->title }}</span>
                                        </span>
                                        <svg class="size-4 text-zinc-300 dark:text-zinc-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="m9 18 6-6-6-6" />
                                        </svg>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <div class="border-t border-zinc-100 p-2 dark:border-zinc-800">
                        <a
                            href="{{ route('storefront.search', ['q' => $this->query]) }}"
                            data-search-result
                            @click="$wire.closeModal()"
                            class="block rounded-lg px-3 py-2.5 text-sm font-medium text-blue-600 transition hover:bg-blue-50 focus:bg-blue-50 focus:outline-none dark:text-blue-400 dark:hover:bg-blue-950/40 dark:focus:bg-blue-950/40"
                        >
                            View all results for &ldquo;{{ $this->query }}&rdquo;
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
