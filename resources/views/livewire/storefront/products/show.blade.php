<div class="flex flex-col gap-8">
    @if ($product)
        <article class="grid grid-cols-1 gap-10 lg:grid-cols-2">
            <div class="aspect-square overflow-hidden rounded-xl bg-neutral-100 dark:bg-neutral-800"></div>
            <div class="flex flex-col gap-4">
                <h1 class="text-3xl font-semibold tracking-tight">{{ $product->title }}</h1>
                @if ($product->description_html ?? false)
                    <div class="prose prose-sm max-w-none dark:prose-invert">
                        {!! $product->description_html !!}
                    </div>
                @endif
            </div>
        </article>
    @else
        <div class="flex flex-col items-center gap-3 rounded-xl border border-dashed border-neutral-300 bg-neutral-50 px-6 py-16 text-center dark:border-neutral-700 dark:bg-neutral-900">
            <h1 class="text-2xl font-semibold tracking-tight">Product not found</h1>
            <p class="text-sm text-neutral-600 dark:text-neutral-400">
                We could not find a product with the handle "{{ $handle }}".
            </p>
            <a href="{{ url('/') }}" class="mt-4 inline-flex items-center rounded-full bg-neutral-900 px-5 py-2 text-sm font-medium text-white hover:bg-neutral-700 dark:bg-white dark:text-neutral-900 dark:hover:bg-neutral-100">
                Back to home
            </a>
        </div>
    @endif
</div>
