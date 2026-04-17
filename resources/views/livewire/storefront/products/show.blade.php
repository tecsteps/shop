<div class="flex flex-col gap-8">
    @if ($product)
        @php($selectedVariant = $product->variants->firstWhere('id', $selected_variant_id) ?? $product->variants->first())
        <article class="grid grid-cols-1 gap-10 lg:grid-cols-2">
            <div class="aspect-square overflow-hidden rounded-xl bg-gradient-to-br from-neutral-100 to-neutral-200 dark:from-neutral-800 dark:to-neutral-900 flex items-center justify-center">
                <span class="text-sm uppercase tracking-wider text-neutral-400">{{ $product->vendor ?? 'Shop' }}</span>
            </div>
            <div class="flex flex-col gap-4">
                <header class="flex flex-col gap-1">
                    <p class="text-xs uppercase tracking-wider text-neutral-500">{{ $product->vendor }}</p>
                    <h1 class="text-3xl font-semibold tracking-tight">{{ $product->title }}</h1>
                    @if ($selectedVariant)
                        <p class="text-2xl font-semibold text-neutral-900 dark:text-white">
                            {{ $selectedVariant->currency }} {{ number_format($selectedVariant->price_amount / 100, 2) }}
                        </p>
                    @endif
                </header>

                @if ($product->description_html)
                    <div class="prose prose-sm max-w-none dark:prose-invert">
                        {!! $product->description_html !!}
                    </div>
                @endif

                @if ($product->variants->count() > 1)
                    <div class="flex flex-col gap-2">
                        <label for="variant-select" class="text-sm font-medium">Variant</label>
                        <select id="variant-select" name="variant_id" wire:model.live="selected_variant_id"
                            class="rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 dark:border-neutral-700 dark:bg-neutral-900">
                            @foreach ($product->variants as $variant)
                                <option wire:key="variant-{{ $variant->id }}" value="{{ $variant->id }}">
                                    {{ $variant->sku }} - {{ $variant->currency }} {{ number_format($variant->price_amount / 100, 2) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="flex flex-col gap-2">
                    <button type="button" wire:click="addToCart"
                        class="inline-flex w-fit items-center gap-2 rounded-full bg-neutral-900 px-6 py-3 text-sm font-semibold text-white transition hover:bg-neutral-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:ring-offset-2 disabled:opacity-60 dark:bg-white dark:text-neutral-900 dark:hover:bg-neutral-200"
                        wire:loading.attr="disabled" wire:target="addToCart">
                        <span wire:loading.remove wire:target="addToCart">Add to Cart</span>
                        <span wire:loading wire:target="addToCart">Adding...</span>
                    </button>
                    @if ($add_to_cart_error !== '')
                        <p class="text-sm text-red-600" role="alert">{{ $add_to_cart_error }}</p>
                    @endif
                </div>
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
