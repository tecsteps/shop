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
                        ${{ number_format($this->selectedVariant->price_amount / 100, 2) }}
                    </p>
                    @if($this->selectedVariant->compare_at_amount)
                        <p class="mt-1 text-sm text-zinc-500 line-through dark:text-zinc-400">
                            ${{ number_format($this->selectedVariant->compare_at_amount / 100, 2) }}
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
            <div class="mt-8">
                <flux:button variant="primary" class="w-full" disabled>
                    Add to Cart
                </flux:button>
                <p class="mt-2 text-center text-xs text-zinc-500 dark:text-zinc-400">Cart functionality coming soon</p>
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
