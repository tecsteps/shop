<section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    <x-storefront.breadcrumbs :items="[
        ['label' => $product->collections->first()?->title ?? 'Products', 'url' => $product->collections->first() ? route('collections.show', $product->collections->first()->handle) : route('collections.index')],
        ['label' => $product->title],
    ]" />

    <div class="mt-8 grid gap-10 lg:grid-cols-2">
        <div class="lg:sticky lg:top-28 lg:self-start">
            <div class="flex aspect-square items-center justify-center rounded-lg border border-zinc-200 bg-zinc-100 text-zinc-400 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-600" role="img" aria-label="{{ $product->title }} image placeholder" data-test="product-image-placeholder">
                <flux:icon name="shopping-bag" class="size-20" />
            </div>
        </div>

        <div class="space-y-8">
            <div class="space-y-4">
                <div class="flex flex-wrap gap-2">
                    @if ($selectedVariant?->compare_at_amount && $selectedVariant->compare_at_amount > $selectedVariant->price_amount)
                        <span class="rounded-full bg-rose-600 px-2.5 py-1 text-xs font-medium text-white">Sale</span>
                    @endif
                    @foreach ($product->tags ?? [] as $tag)
                        <span class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">{{ $tag }}</span>
                    @endforeach
                </div>

                <h1 class="text-3xl font-semibold tracking-normal text-zinc-950 dark:text-white md:text-4xl">{{ $product->title }}</h1>

                @if ($selectedVariant)
                    <x-storefront.price :amount="$selectedVariant->price_amount" :compare-at="$selectedVariant->compare_at_amount" :currency="$selectedVariant->currency" class="text-2xl" aria-live="polite" />
                @endif
            </div>

            @if ($product->options->isNotEmpty())
                <div class="space-y-6">
                    @foreach ($product->options as $option)
                        <fieldset class="space-y-3" wire:key="product-option-selector-{{ $option->getKey() }}">
                            <legend class="text-sm font-medium text-zinc-950 dark:text-white">{{ $option->name }}</legend>

                            <div class="flex flex-wrap gap-2">
                                @foreach ($option->values as $value)
                                    @php($selected = ($selectedOptions[$option->name] ?? null) === $value->value)
                                    <button
                                        type="button"
                                        wire:click="selectOption(@js($option->name), @js($value->value))"
                                        @class([
                                            'min-h-10 rounded-md border px-4 py-2 text-sm font-medium transition',
                                            'border-blue-700 bg-blue-50 text-blue-800 dark:border-blue-300 dark:bg-blue-950 dark:text-blue-100' => $selected,
                                            'border-zinc-200 text-zinc-700 hover:border-zinc-400 dark:border-zinc-700 dark:text-zinc-200 dark:hover:border-zinc-500' => ! $selected,
                                        ])
                                        aria-pressed="{{ $selected ? 'true' : 'false' }}"
                                    >
                                        {{ $value->value }}
                                    </button>
                                @endforeach
                            </div>
                        </fieldset>
                    @endforeach
                </div>
            @endif

            <div class="space-y-4">
                <p class="text-sm font-medium {{ $stockState['class'] }}" aria-live="polite">{{ $stockState['message'] }}</p>

                <div class="flex max-w-44 items-center rounded-md border border-zinc-200 dark:border-zinc-700">
                    <button type="button" wire:click="decreaseQuantity" class="flex size-11 items-center justify-center border-r border-zinc-200 text-lg dark:border-zinc-700" aria-label="Decrease quantity">-</button>
                    <input wire:model.live="quantity" type="number" min="1" class="h-11 w-full border-0 bg-transparent text-center text-sm" aria-label="Quantity">
                    <button type="button" wire:click="increaseQuantity" class="flex size-11 items-center justify-center border-l border-zinc-200 text-lg dark:border-zinc-700" aria-label="Increase quantity">+</button>
                </div>

                <flux:button wire:click="addToCart" variant="primary" class="w-full" :disabled="! $this->canAddToCart()">
                    {{ $this->canAddToCart() ? 'Add to cart' : 'Sold out' }}
                </flux:button>
            </div>

            @if ($product->description_html)
                <div class="border-t border-zinc-200 pt-8 text-zinc-700 dark:border-zinc-800 dark:text-zinc-300">
                    {!! $product->description_html !!}
                </div>
            @endif

            <dl class="grid gap-4 border-t border-zinc-200 pt-8 text-sm dark:border-zinc-800 sm:grid-cols-2">
                <div>
                    <dt class="font-medium text-zinc-950 dark:text-white">Vendor</dt>
                    <dd class="mt-1 text-zinc-600 dark:text-zinc-400">{{ $product->vendor ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-zinc-950 dark:text-white">Product type</dt>
                    <dd class="mt-1 text-zinc-600 dark:text-zinc-400">{{ $product->product_type ?: '-' }}</dd>
                </div>
                @if ($selectedVariant)
                    <div>
                        <dt class="font-medium text-zinc-950 dark:text-white">SKU</dt>
                        <dd class="mt-1 text-zinc-600 dark:text-zinc-400">{{ $selectedVariant->sku ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-zinc-950 dark:text-white">Shipping</dt>
                        <dd class="mt-1 text-zinc-600 dark:text-zinc-400">{{ $selectedVariant->requires_shipping ? 'Required' : 'Digital delivery' }}</dd>
                    </div>
                @endif
            </dl>
        </div>
    </div>
</section>
