<div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    <x-storefront.breadcrumbs :items="[
        ['label' => 'Home', 'url' => route('storefront.home')],
        ['label' => 'Products', 'url' => url('/collections')],
        ['label' => $product->title],
    ]" class="mb-6" />

    @if (session('success'))
        <div class="mb-6 rounded-lg bg-green-50 p-4 text-sm text-green-700 dark:bg-green-900/30 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif

    <div class="lg:grid lg:grid-cols-2 lg:gap-12">
        {{-- Image Gallery --}}
        <div class="lg:sticky lg:top-24">
            <div class="aspect-square overflow-hidden rounded-xl bg-gray-100 dark:bg-gray-800">
                @if ($product->media->isNotEmpty())
                    <img src="{{ $product->media->first()->storage_key }}" alt="{{ $product->media->first()->alt_text ?? $product->title }}" class="h-full w-full object-cover" />
                @else
                    <div class="flex h-full items-center justify-center text-gray-400 dark:text-gray-600">
                        <svg class="h-20 w-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                    </div>
                @endif
            </div>
        </div>

        {{-- Product Info --}}
        <div class="mt-8 lg:mt-0">
            <h1 class="text-2xl font-bold text-gray-900 lg:text-3xl dark:text-white">{{ $product->title }}</h1>

            <div class="mt-4" aria-live="polite">
                @if ($selectedVariant)
                    <span class="text-2xl font-bold text-gray-900 dark:text-white">${{ number_format($selectedVariant->price_amount / 100, 2) }}</span>
                    @if ($selectedVariant->compare_at_amount && $selectedVariant->compare_at_amount > $selectedVariant->price_amount)
                        <span class="ml-2 text-lg text-gray-500 line-through">${{ number_format($selectedVariant->compare_at_amount / 100, 2) }}</span>
                    @endif
                @endif
            </div>

            <div class="mt-4 flex items-center gap-2 text-sm text-green-600">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m4.5 12.75 6 6 9-13.5"/></svg>
                In stock
            </div>

            {{-- Variant Options --}}
            @foreach ($product->options as $option)
                <div class="mt-6">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $option->name }}</label>
                    <select wire:model.live="selectedOptions.{{ $option->id }}" class="mt-2 w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        @foreach ($option->values as $value)
                            <option value="{{ $value->id }}">{{ $value->value }}</option>
                        @endforeach
                    </select>
                </div>
            @endforeach

            {{-- Quantity --}}
            <div class="mt-6">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Quantity</label>
                <div class="mt-2">
                    <x-storefront.quantity-selector :value="$quantity" wire-model="quantity" />
                </div>
            </div>

            {{-- Add to Cart --}}
            <button
                wire:click="addToCart"
                class="mt-6 w-full rounded-lg bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
            >
                Add to cart
            </button>

            {{-- Description --}}
            @if ($product->description_html)
                <div class="mt-8 border-t border-gray-200 pt-8 dark:border-gray-800">
                    <div class="prose dark:prose-invert">
                        {!! $product->description_html !!}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
