<div class="flex flex-col gap-8">
    <section class="flex flex-col gap-4">
        <h1 class="text-3xl font-semibold tracking-tight">Search</h1>
        <form wire:submit.prevent method="get" class="flex gap-2">
            <label for="search-input" class="sr-only">Search products</label>
            <input
                id="search-input"
                type="search"
                name="q"
                wire:model.live.debounce.300ms="query"
                placeholder="Search products..."
                class="flex-1 rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm shadow-sm focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100"
            />
        </form>
    </section>

    @if (trim($query) !== '')
        <section class="flex flex-col gap-6">
            <p class="text-sm text-neutral-500 dark:text-neutral-400">
                {{ $results->count() }} {{ Str::plural('result', $results->count()) }} for "{{ $query }}"
            </p>

            @if ($results->isEmpty())
                <div class="rounded-lg border border-dashed border-neutral-300 bg-neutral-50 p-8 text-center text-sm text-neutral-500 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-400">
                    No products matched your search.
                </div>
            @else
                <ul class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($results as $product)
                        @php
                            $variant = $product->variants->firstWhere('status', \App\Enums\VariantStatus::Active) ?? $product->variants->first();
                        @endphp
                        <li wire:key="search-{{ $product->getKey() }}" class="flex flex-col gap-2 rounded-xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                            <a href="{{ url('/products/'.$product->handle) }}" class="text-base font-semibold hover:underline">
                                {{ $product->title }}
                            </a>
                            @if ($variant)
                                <span class="text-sm text-neutral-600 dark:text-neutral-400">
                                    {{ $variant->currency }} {{ number_format($variant->price_amount / 100, 2) }}
                                </span>
                            @endif
                            @if ($product->vendor)
                                <span class="text-xs uppercase tracking-wide text-neutral-500 dark:text-neutral-500">
                                    {{ $product->vendor }}
                                </span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    @endif
</div>
