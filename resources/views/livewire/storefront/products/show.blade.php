<div class="grid grid-cols-1 gap-10 lg:grid-cols-2">
    <div class="flex flex-col gap-4">
        <div class="aspect-square overflow-hidden rounded-2xl bg-zinc-100 dark:bg-zinc-800">
            @php($image = $product->media->first())
            @if ($image)
                <img src="{{ asset('storage/'.$image->storage_key) }}" alt="{{ $image->alt_text ?? $product->title }}" class="h-full w-full object-cover" />
            @else
                <div class="flex h-full w-full items-center justify-center text-zinc-400">No image</div>
            @endif
        </div>
        @if ($product->media->count() > 1)
            <div class="grid grid-cols-4 gap-2">
                @foreach ($product->media->skip(1) as $m)
                    <div class="aspect-square overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800">
                        <img src="{{ asset('storage/'.$m->storage_key) }}" alt="" class="h-full w-full object-cover" />
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="flex flex-col gap-6">
        <div>
            <div class="text-xs font-medium uppercase tracking-wide text-zinc-500">{{ $product->vendor }}</div>
            <h1 class="mt-1 text-3xl font-bold tracking-tight">{{ $product->title }}</h1>
            @if ($variant)
                <div class="mt-2 text-2xl font-semibold" data-testid="product-price">
                    {{ $variant->currency }} {{ number_format($variant->price_amount / 100, 2) }}
                </div>
            @endif
        </div>

        @if ($product->options->isNotEmpty())
            <div class="flex flex-col gap-4" data-testid="product-options">
                @foreach ($product->options as $option)
                    <div>
                        <div class="mb-2 text-sm font-medium">{{ $option->name }}</div>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($product->variants as $v)
                                @php($match = $v->optionValues->firstWhere('product_option_id', $option->id))
                                @if ($match)
                                    <button
                                        type="button"
                                        wire:click="$set('variantId', {{ $v->id }})"
                                        class="rounded-lg border px-3 py-1.5 text-sm {{ $variantId === $v->id ? 'border-zinc-900 bg-zinc-900 text-white dark:border-white dark:bg-white dark:text-zinc-900' : 'border-zinc-300 bg-white text-zinc-900 hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white dark:hover:bg-zinc-700' }}"
                                    >
                                        {{ $match->value }}
                                    </button>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="flex items-center gap-3">
            <div class="inline-flex items-center overflow-hidden rounded-lg border border-zinc-300 dark:border-zinc-700">
                <button type="button" wire:click="decrementQuantity" class="px-3 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-800">−</button>
                <span class="min-w-8 px-3 py-2 text-center" data-testid="product-quantity">{{ $quantity }}</span>
                <button type="button" wire:click="incrementQuantity" class="px-3 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-800">+</button>
            </div>
            <flux:button
                wire:click="addToCart"
                variant="primary"
                class="flex-1"
                data-testid="add-to-cart"
            >
                Add to cart
            </flux:button>
        </div>

        @if ($variant && $variant->inventory && !$variant->inventory->canFulfill($quantity))
            <flux:callout variant="danger" icon="exclamation-triangle" heading="Out of stock"></flux:callout>
        @endif

        <div class="prose max-w-none text-zinc-700 dark:prose-invert dark:text-zinc-300">
            {!! $product->description_html !!}
        </div>
    </div>
</div>
