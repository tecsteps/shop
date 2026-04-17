<div>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="lg:grid lg:grid-cols-2 lg:gap-x-8">
            {{-- Product Images --}}
            <div class="aspect-square overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-800">
                @if($product->media->isNotEmpty())
                    <img src="{{ $product->media->first()->url }}" alt="{{ $product->title }}" class="h-full w-full object-cover object-center">
                @else
                    <div class="flex h-full items-center justify-center text-gray-400 dark:text-gray-500">
                        <flux:icon name="photo" class="size-16" />
                    </div>
                @endif
            </div>

            {{-- Product Info --}}
            <div class="mt-8 lg:mt-0">
                @if($product->vendor)
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $product->vendor }}</p>
                @endif

                <h1 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white lg:text-3xl">{{ $product->title }}</h1>

                @if($defaultVariant)
                    <p class="mt-4 text-2xl font-semibold text-gray-900 dark:text-white">
                        ${{ number_format($defaultVariant->price_amount / 100, 2) }}
                        @if($defaultVariant->compare_at_amount && $defaultVariant->compare_at_amount > $defaultVariant->price_amount)
                            <span class="ml-2 text-lg text-gray-500 line-through dark:text-gray-400">${{ number_format($defaultVariant->compare_at_amount / 100, 2) }}</span>
                        @endif
                    </p>
                @endif

                @if($product->description_html)
                    <div class="prose prose-sm mt-6 dark:prose-invert">
                        {!! $product->description_html !!}
                    </div>
                @endif

                {{-- Variant Options --}}
                @if($product->options->isNotEmpty())
                    <div class="mt-6 space-y-4">
                        @foreach($product->options as $option)
                            <div>
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $option->name }}</label>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach($option->values as $value)
                                        <span class="inline-flex items-center rounded-md border border-gray-300 px-3 py-1 text-sm text-gray-700 dark:border-gray-600 dark:text-gray-300">{{ $value->value }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Add to Cart --}}
                @if($defaultVariant)
                    @php
                        $available = $defaultVariant->inventoryItem?->available ?? 0;
                        $backorderAllowed = $defaultVariant->inventoryItem?->policy === \App\Enums\InventoryPolicy::Continue;
                    @endphp
                    @if($available > 0 || $backorderAllowed)
                        <div class="mt-8">
                            <flux:button wire:click="addToCart" variant="primary" class="w-full">Add to Cart</flux:button>
                        </div>
                    @else
                        <div class="mt-8">
                            <flux:badge color="red">Out of Stock</flux:badge>
                        </div>
                    @endif
                @endif

                @if($addedMessage)
                    <div class="mt-4">
                        <flux:badge color="green">{{ $addedMessage }}</flux:badge>
                    </div>
                @endif

                @if($errorMessage)
                    <div class="mt-4">
                        <flux:badge color="red">{{ $errorMessage }}</flux:badge>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
