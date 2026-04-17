@php
    $store = app()->bound('current_store') ? app('current_store') : null;
    $currency = $store?->default_currency ?? 'EUR';
@endphp

<div>
    @if($product)
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <x-storefront.breadcrumbs :items="[
                ['label' => 'Products', 'url' => '/collections'],
                ['label' => $product->title],
            ]" />

            <div class="lg:flex lg:gap-12">
                {{-- Image Gallery --}}
                <div class="lg:w-1/2 lg:sticky lg:top-24 lg:self-start">
                    <div class="aspect-square overflow-hidden rounded-xl bg-gray-100 dark:bg-gray-800" role="region" aria-label="Product images">
                        @php
                            $media = $product->media ?? collect();
                            $primaryImage = $media->first();
                        @endphp
                        @if($primaryImage)
                            <img src="{{ $primaryImage->url }}"
                                 alt="{{ $primaryImage->alt_text ?? $product->title }}"
                                 class="h-full w-full object-cover">
                        @else
                            <div class="flex h-full w-full items-center justify-center text-gray-400 dark:text-gray-600">
                                <svg class="h-24 w-24" fill="none" viewBox="0 0 24 24" stroke-width="0.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                </svg>
                            </div>
                        @endif
                    </div>

                    {{-- Thumbnail Strip --}}
                    @if($media->count() > 1)
                        <div class="mt-4 flex gap-2 overflow-x-auto">
                            @foreach($media as $image)
                                <button class="h-16 w-16 shrink-0 overflow-hidden rounded-md border-2 transition-colors {{ $loop->first ? 'border-blue-500' : 'border-gray-200 dark:border-gray-700 hover:border-gray-400' }}"
                                        aria-label="View image {{ $loop->iteration }} of {{ $media->count() }}">
                                    <img src="{{ $image->url }}"
                                         alt=""
                                         class="h-full w-full object-cover">
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Product Info --}}
                <div class="mt-8 lg:mt-0 lg:w-1/2">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white lg:text-3xl">
                        {{ $product->title }}
                    </h1>

                    {{-- Price --}}
                    <div class="mt-4 flex items-center gap-3" aria-live="polite">
                        @php
                            $displayPrice = $selectedVariant?->price_amount ?? $product->price_amount ?? 0;
                            $compareAtPrice = $selectedVariant?->compare_at_price_amount ?? $product->compare_at_price_amount ?? null;
                            $isOnSale = $compareAtPrice && $compareAtPrice > $displayPrice;
                        @endphp
                        <x-storefront.price :amount="$displayPrice" :currency="$currency" class="text-2xl font-bold text-gray-900 dark:text-white" />
                        @if($isOnSale)
                            <x-storefront.price :amount="$compareAtPrice" :currency="$currency" class="text-lg text-gray-500 line-through dark:text-gray-400" />
                            <x-storefront.badge variant="sale">Sale</x-storefront.badge>
                        @endif
                    </div>

                    {{-- Variant Selector --}}
                    @if(($product->options ?? collect())->isNotEmpty())
                        <div class="mt-6 space-y-4">
                            @foreach($product->options as $option)
                                <fieldset>
                                    <legend class="text-sm font-semibold text-gray-900 dark:text-white">{{ $option->name }}</legend>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @foreach($option->values as $value)
                                            <label class="cursor-pointer">
                                                <input type="radio"
                                                       wire:model.live="selectedOptions.{{ $option->name }}"
                                                       value="{{ $value->value }}"
                                                       class="peer sr-only"
                                                       name="option_{{ $option->id }}">
                                                <span class="inline-flex items-center rounded-md border px-3 py-1.5 text-sm font-medium transition-colors peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:text-blue-700 peer-focus:ring-2 peer-focus:ring-blue-500 peer-focus:ring-offset-2 border-gray-300 text-gray-700 hover:border-gray-400 dark:border-gray-600 dark:text-gray-300 dark:peer-checked:bg-blue-900/30 dark:peer-checked:text-blue-400">
                                                    {{ $value->value }}
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </fieldset>
                            @endforeach
                        </div>
                    @endif

                    {{-- Quantity --}}
                    <div class="mt-6">
                        <label class="text-sm font-semibold text-gray-900 dark:text-white">Quantity</label>
                        <div class="mt-2">
                            <x-storefront.quantity-selector :value="$quantity" wire-model="quantity" />
                        </div>
                    </div>

                    {{-- Add to Cart --}}
                    <div class="mt-8">
                        <button wire:click="addToCart"
                                wire:loading.attr="disabled"
                                class="w-full rounded-md bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:cursor-not-allowed disabled:opacity-50">
                            <span wire:loading.remove wire:target="addToCart">Add to cart</span>
                            <span wire:loading wire:target="addToCart">Adding...</span>
                        </button>
                    </div>

                    {{-- Description --}}
                    @if($product->description_html)
                        <div class="mt-8 border-t border-gray-200 pt-8 dark:border-gray-800">
                            <div class="prose dark:prose-invert max-w-none">
                                {!! $product->description_html !!}
                            </div>
                        </div>
                    @endif

                    {{-- Tags --}}
                    @if(! empty($product->tags))
                        <div class="mt-6 flex flex-wrap gap-2">
                            @foreach($product->tags as $tag)
                                <span class="rounded-full bg-gray-100 px-3 py-1 text-sm text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                    {{ $tag }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @else
        <div class="mx-auto max-w-7xl px-4 py-16 text-center sm:px-6 lg:px-8">
            <p class="text-lg text-gray-600 dark:text-gray-400">Product not found.</p>
        </div>
    @endif
</div>
