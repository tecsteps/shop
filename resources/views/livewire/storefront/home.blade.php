<div class="flex flex-col gap-12">
    <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-neutral-900 to-neutral-700 px-6 py-16 text-white sm:px-12 sm:py-24 dark:from-neutral-800 dark:to-neutral-950">
        <div class="max-w-2xl">
            <h1 class="text-4xl font-semibold tracking-tight sm:text-5xl">
                Welcome to {{ $currentStore->name }}
            </h1>
            <p class="mt-4 text-base text-neutral-200 sm:text-lg">
                Discover a curated selection of goods, shipped fast and backed by care.
            </p>
            <div class="mt-8">
                <a href="{{ url('/collections/featured') }}"
                    class="inline-flex items-center rounded-full bg-white px-6 py-3 text-sm font-semibold text-neutral-900 transition hover:bg-neutral-100">
                    Shop the collection
                </a>
            </div>
        </div>
    </section>

    <section class="flex flex-col gap-6">
        <div class="flex items-end justify-between gap-4">
            <h2 class="text-2xl font-semibold tracking-tight">Featured</h2>
            <a href="{{ url('/collections/featured') }}" class="text-sm font-medium text-neutral-600 hover:text-neutral-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 dark:text-neutral-400 dark:hover:text-white">
                View all
            </a>
        </div>
        @if ($featuredProducts->isEmpty())
            <div class="rounded-xl border border-dashed border-neutral-300 bg-neutral-50 p-6 text-sm text-neutral-500 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-400">
                No products yet.
            </div>
        @else
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($featuredProducts as $product)
                    @php($variant = $product->variants->first())
                    <a wire:key="product-{{ $product->id }}" href="{{ url('/products/'.$product->handle) }}"
                        class="group flex flex-col overflow-hidden rounded-xl border border-neutral-200 bg-white transition hover:border-neutral-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 dark:border-neutral-800 dark:bg-neutral-900 dark:hover:border-neutral-600">
                        <div class="aspect-square bg-gradient-to-br from-neutral-100 to-neutral-200 dark:from-neutral-800 dark:to-neutral-900 flex items-center justify-center">
                            <span class="text-xs uppercase tracking-wide text-neutral-400">{{ $product->vendor ?? 'Shop' }}</span>
                        </div>
                        <div class="flex flex-col gap-1 p-4">
                            <h3 class="text-sm font-medium text-neutral-900 dark:text-white">{{ $product->title }}</h3>
                            @if ($variant)
                                <p class="text-sm text-neutral-600 dark:text-neutral-400">
                                    {{ $variant->currency }} {{ number_format($variant->price_amount / 100, 2) }}
                                </p>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </section>
</div>
