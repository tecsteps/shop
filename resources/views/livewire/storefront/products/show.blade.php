<div>
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
        {{-- Product Images --}}
        <div class="aspect-square rounded-lg bg-zinc-100 dark:bg-zinc-800">
            @if($product->media->isNotEmpty())
                <img src="{{ $product->media->first()->url }}" alt="{{ $product->title }}" class="h-full w-full rounded-lg object-cover">
            @endif
        </div>

        {{-- Product Details --}}
        <div>
            <h1 class="text-3xl font-bold tracking-tight">{{ $product->title }}</h1>

            @if($this->selectedVariant)
                <div class="mt-4">
                    <p class="text-2xl font-semibold">
                        {{ number_format($this->selectedVariant->price_amount / 100, 2) }} EUR
                    </p>
                    @if($this->selectedVariant->compare_at_amount)
                        <p class="mt-1 text-sm text-zinc-500 line-through dark:text-zinc-400">
                            {{ number_format($this->selectedVariant->compare_at_amount / 100, 2) }} EUR
                        </p>
                    @endif
                </div>
            @endif

            {{-- Variant Selector --}}
            @if($product->variants->count() > 1)
                <div class="mt-6">
                    <h3 class="text-sm font-medium text-zinc-900 dark:text-zinc-100">Variants</h3>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach($product->variants as $variant)
                            <button wire:key="variant-{{ $variant->id }}"
                                    wire:click="selectVariant({{ $variant->id }})"
                                    class="rounded-md border px-4 py-2 text-sm transition-colors {{ $selectedVariantId === $variant->id ? 'border-zinc-900 bg-zinc-900 text-white dark:border-zinc-100 dark:bg-zinc-100 dark:text-zinc-900' : 'border-zinc-300 text-zinc-700 hover:border-zinc-500 dark:border-zinc-600 dark:text-zinc-300 dark:hover:border-zinc-400' }}">
                                {{ $variant->sku ?: 'Variant '.$loop->iteration }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Add to Cart --}}
            <div class="mt-8 space-y-4">
                {{-- Flash messages --}}
                @if(session('cart-success'))
                    <div class="rounded-md bg-green-50 p-3 text-sm text-green-700 dark:bg-green-900/30 dark:text-green-400">
                        {{ session('cart-success') }}
                    </div>
                @endif
                @if(session('cart-error'))
                    <div class="rounded-md bg-red-50 p-3 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-400">
                        {{ session('cart-error') }}
                    </div>
                @endif

                {{-- Stock status --}}
                @php
                    $stockStatus = $this->stockStatus;
                    $stockColorClass = match(true) {
                        $stockStatus === 'Sold out' => 'text-red-600 dark:text-red-400',
                        str_starts_with($stockStatus, 'Low stock') => 'text-amber-600 dark:text-amber-400',
                        $stockStatus === 'Available on backorder' => 'text-amber-600 dark:text-amber-400',
                        default => 'text-green-600 dark:text-green-400',
                    };
                @endphp
                <p class="text-sm {{ $stockColorClass }}">
                    {{ $stockStatus }}
                </p>

                {{-- Quantity selector --}}
                <div class="flex items-center gap-2">
                    <button
                        wire:click="$set('quantity', {{ max(1, $quantity - 1) }})"
                        class="flex h-10 w-10 items-center justify-center rounded-md border border-zinc-300 text-zinc-700 transition-colors hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-800"
                        @if($quantity <= 1) disabled @endif
                    >
                        -
                    </button>
                    <input
                        type="number"
                        wire:model.live="quantity"
                        min="1"
                        class="h-10 w-16 rounded-md border border-zinc-300 text-center text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                    />
                    <button
                        wire:click="$set('quantity', {{ $quantity + 1 }})"
                        class="flex h-10 w-10 items-center justify-center rounded-md border border-zinc-300 text-zinc-700 transition-colors hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-800"
                    >
                        +
                    </button>
                </div>

                {{-- Add to Cart button --}}
                <flux:button
                    wire:click="addToCart"
                    wire:loading.attr="disabled"
                    variant="primary"
                    class="w-full"
                    :disabled="!$this->canAddToCart"
                >
                    {{ $this->canAddToCart ? 'Add to Cart' : 'Sold Out' }}
                </flux:button>
            </div>

            {{-- Description --}}
            @if($product->description_html)
                <div class="mt-8 border-t border-zinc-200 pt-8 dark:border-zinc-700">
                    <h3 class="text-sm font-medium text-zinc-900 dark:text-zinc-100">Description</h3>
                    <div class="prose prose-sm mt-4 dark:prose-invert">
                        {!! $product->description_html !!}
                    </div>
                </div>
            @endif

            {{-- Product Meta --}}
            <div class="mt-8 border-t border-zinc-200 pt-8 dark:border-zinc-700">
                <dl class="space-y-4 text-sm">
                    @if($product->vendor)
                        <div class="flex justify-between">
                            <dt class="text-zinc-500 dark:text-zinc-400">Vendor</dt>
                            <dd class="font-medium">{{ $product->vendor }}</dd>
                        </div>
                    @endif
                    @if($product->product_type)
                        <div class="flex justify-between">
                            <dt class="text-zinc-500 dark:text-zinc-400">Type</dt>
                            <dd class="font-medium">{{ $product->product_type }}</dd>
                        </div>
                    @endif
                    @if($this->selectedVariant?->sku)
                        <div class="flex justify-between">
                            <dt class="text-zinc-500 dark:text-zinc-400">SKU</dt>
                            <dd class="font-medium">{{ $this->selectedVariant->sku }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>
    </div>
</div>
