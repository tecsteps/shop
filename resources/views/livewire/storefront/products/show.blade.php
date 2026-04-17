<div class="space-y-12">
    <div class="grid gap-10 lg:grid-cols-2">
        <div class="space-y-4">
            <div class="aspect-square overflow-hidden rounded-2xl bg-gradient-to-br from-zinc-100 to-zinc-200 dark:from-zinc-800 dark:to-zinc-900"></div>
            @if ($product->media && $product->media->count() > 1)
                <div class="grid grid-cols-4 gap-3">
                    @foreach ($product->media->take(4) as $media)
                        <div class="aspect-square overflow-hidden rounded-xl bg-gradient-to-br from-zinc-100 to-zinc-200 dark:from-zinc-800 dark:to-zinc-900" aria-label="{{ $media->alt_text }}"></div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="space-y-8">
            <div class="space-y-2">
                @if ($product->vendor)
                    <p class="text-xs font-semibold uppercase tracking-widest text-zinc-500 dark:text-zinc-400">{{ $product->vendor }}</p>
                @endif
                <h1 class="text-3xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-50 sm:text-4xl">{{ $product->title }}</h1>
                @if ($selectedVariant)
                    <p class="text-2xl text-zinc-900 dark:text-zinc-100">
                        <x-storefront.price :amount="$selectedVariant->price_amount" :currency="$selectedVariant->currency" />
                    </p>
                @endif
            </div>

            @if ($activeVariants->count() > 1)
                <fieldset class="space-y-3">
                    <legend class="text-sm font-medium text-zinc-900 dark:text-zinc-100">Variant</legend>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($activeVariants as $variant)
                            <button
                                type="button"
                                wire:click="selectVariant({{ $variant->id }})"
                                class="rounded-full border px-4 py-2 text-sm font-medium transition {{ $selectedVariantId === $variant->id ? 'border-zinc-900 bg-zinc-900 text-white dark:border-white dark:bg-white dark:text-zinc-900' : 'border-zinc-300 bg-white text-zinc-700 hover:border-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:border-zinc-100' }}"
                            >
                                {{ $variant->sku }}
                            </button>
                        @endforeach
                    </div>
                </fieldset>
            @endif

            <div class="space-y-3">
                <label class="block text-sm font-medium text-zinc-900 dark:text-zinc-100">Quantity</label>
                <div class="inline-flex items-center rounded-full border border-zinc-300 dark:border-zinc-700">
                    <button type="button" wire:click="decrementQuantity" aria-label="Decrease quantity" class="px-4 py-2 text-lg text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">-</button>
                    <span class="w-10 text-center text-sm font-medium tabular-nums text-zinc-900 dark:text-zinc-100">{{ $quantity }}</span>
                    <button type="button" wire:click="incrementQuantity" aria-label="Increase quantity" class="px-4 py-2 text-lg text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">+</button>
                </div>
            </div>

            @if (session('cart-success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200">
                    {{ session('cart-success') }}
                </div>
            @endif

            @error('cart')
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200">
                    {{ $message }}
                </div>
            @enderror

            <button
                type="button"
                wire:click="addToCart"
                wire:loading.attr="disabled"
                class="inline-flex w-full items-center justify-center rounded-full bg-zinc-900 px-8 py-4 text-sm font-semibold text-white transition hover:bg-zinc-700 disabled:opacity-60 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200"
            >
                <span wire:loading.remove wire:target="addToCart">Add to cart</span>
                <span wire:loading wire:target="addToCart">Adding...</span>
            </button>

            @if ($product->description_html)
                <div class="prose prose-sm prose-zinc max-w-none border-t border-zinc-200 pt-8 text-zinc-700 dark:prose-invert dark:border-zinc-800 dark:text-zinc-300">
                    {!! $product->description_html !!}
                </div>
            @endif
        </div>
    </div>
</div>
