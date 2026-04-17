<div class="flex flex-col gap-8">
    @if ($collection)
        <header class="flex flex-col gap-2">
            <h1 class="text-3xl font-semibold tracking-tight">{{ $collection->title }}</h1>
            @if ($collection->description_html)
                <div class="prose prose-sm max-w-none dark:prose-invert">
                    {!! $collection->description_html !!}
                </div>
            @endif
        </header>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($collection->products as $product)
                <a wire:key="product-{{ $product->id }}" href="{{ url('/products/'.$product->handle) }}"
                    class="group block overflow-hidden rounded-xl border border-neutral-200 bg-white transition hover:border-neutral-400 dark:border-neutral-800 dark:bg-neutral-900 dark:hover:border-neutral-600">
                    <div class="aspect-square bg-neutral-100 dark:bg-neutral-800"></div>
                    <div class="p-4">
                        <h2 class="text-sm font-medium">{{ $product->title }}</h2>
                    </div>
                </a>
            @endforeach
        </div>
    @else
        <div class="flex flex-col items-center gap-3 rounded-xl border border-dashed border-neutral-300 bg-neutral-50 px-6 py-16 text-center dark:border-neutral-700 dark:bg-neutral-900">
            <h1 class="text-2xl font-semibold tracking-tight">Collection not found</h1>
            <p class="text-sm text-neutral-600 dark:text-neutral-400">
                We could not find a collection with the handle "{{ $handle }}".
            </p>
            <a href="{{ url('/') }}" class="mt-4 inline-flex items-center rounded-full bg-neutral-900 px-5 py-2 text-sm font-medium text-white hover:bg-neutral-700 dark:bg-white dark:text-neutral-900 dark:hover:bg-neutral-100">
                Back to home
            </a>
        </div>
    @endif
</div>
