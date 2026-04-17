<div>
    <x-slot:title>{{ $product->title }}</x-slot:title>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="lg:grid lg:grid-cols-2 lg:gap-x-8">
            {{-- Image Gallery --}}
            <div class="aspect-square overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-800">
                <div class="flex h-full items-center justify-center text-gray-400">
                    <svg class="h-24 w-24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>

            {{-- Product Info --}}
            <div class="mt-8 lg:mt-0">
                <h1 class="text-3xl font-bold">{{ $product->title }}</h1>

                @if ($selectedVariant)
                    <div class="mt-4">
                        <span class="text-2xl font-semibold">{{ number_format($selectedVariant->price_amount / 100, 2) }} {{ $selectedVariant->currency }}</span>
                        @if ($selectedVariant->compare_at_amount)
                            <span class="ml-2 text-lg text-gray-500 line-through">{{ number_format($selectedVariant->compare_at_amount / 100, 2) }} {{ $selectedVariant->currency }}</span>
                        @endif
                    </div>
                @endif

                @if ($product->description_html)
                    <div class="prose mt-4 max-w-none text-gray-600 dark:text-gray-400">
                        {!! $product->description_html !!}
                    </div>
                @endif

                {{-- Variant Selection --}}
                @if ($product->variants->count() > 1)
                    <div class="mt-6">
                        <label for="variant" class="text-sm font-medium">Variant</label>
                        <select id="variant" wire:model.live="selectedVariantId"
                                class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-800">
                            @foreach ($product->variants as $variant)
                                <option value="{{ $variant->id }}">
                                    {{ $variant->sku ?? 'Variant '.$variant->position }}
                                    - {{ number_format($variant->price_amount / 100, 2) }} {{ $variant->currency }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                {{-- Quantity --}}
                <div class="mt-6">
                    <label for="quantity" class="text-sm font-medium">Quantity</label>
                    <div class="mt-1 flex items-center space-x-3">
                        <button wire:click="decrementQuantity"
                                class="rounded-lg border border-gray-300 px-3 py-1 dark:border-gray-600">-</button>
                        <span class="w-12 text-center">{{ $quantity }}</span>
                        <button wire:click="incrementQuantity"
                                class="rounded-lg border border-gray-300 px-3 py-1 dark:border-gray-600">+</button>
                    </div>
                </div>

                {{-- Add to Cart --}}
                <button wire:click="addToCart"
                        class="mt-8 w-full rounded-lg bg-blue-600 px-8 py-3 text-white hover:bg-blue-700">
                    Add to Cart
                </button>

                @if ($addedMessage)
                    <p class="mt-2 text-sm font-medium text-green-600">{{ $addedMessage }}</p>
                @endif
            </div>
        </div>
    </div>
</div>
