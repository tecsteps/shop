<div>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <x-storefront.components.breadcrumbs :items="[
            ['label' => 'Home', 'url' => '/'],
            ['label' => 'Products', 'url' => '/collections'],
            ['label' => $product->title, 'url' => null],
        ]" class="mb-6" />

        <div class="lg:grid lg:grid-cols-2 lg:gap-x-12">
            {{-- Image gallery --}}
            <div class="lg:sticky lg:top-24">
                @php
                    $images = $product->media->sortBy('position');
                    $mainImage = $images->first();
                @endphp

                <div class="aspect-square overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-800">
                    @if($mainImage)
                        <img src="{{ Storage::url($mainImage->storage_key) }}"
                             alt="{{ $mainImage->alt_text ?? $product->title }}"
                             class="h-full w-full object-cover">
                    @else
                        <div class="flex h-full w-full items-center justify-center">
                            <svg class="h-16 w-16 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                            </svg>
                        </div>
                    @endif
                </div>

                {{-- Thumbnails --}}
                @if($images->count() > 1)
                    <div class="mt-4 flex gap-3 overflow-x-auto">
                        @foreach($images as $image)
                            <button class="h-16 w-16 flex-shrink-0 overflow-hidden rounded-md border-2 border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    aria-label="View image {{ $loop->iteration }} of {{ $images->count() }}">
                                <img src="{{ Storage::url($image->storage_key) }}"
                                     alt="{{ $image->alt_text ?? '' }}"
                                     class="h-full w-full object-cover">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Product info --}}
            <div class="mt-8 lg:mt-0">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white lg:text-3xl">
                    {{ $product->title }}
                </h1>

                {{-- Price --}}
                @if($selectedVariant)
                    <div class="mt-4" aria-live="polite">
                        <x-storefront.components.price
                            :amount="$selectedVariant->price_amount"
                            :currency="$selectedVariant->currency"
                            :compare-at-amount="$selectedVariant->compare_at_amount"
                            class="text-xl font-bold"
                        />
                        @if($selectedVariant->compare_at_amount && $selectedVariant->compare_at_amount > $selectedVariant->price_amount)
                            <x-storefront.components.badge text="Sale" variant="sale" class="ml-2" />
                        @endif
                    </div>
                @endif

                {{-- Variant selectors --}}
                @if($product->options->isNotEmpty())
                    <div class="mt-6 space-y-5">
                        @foreach($product->options->sortBy('position') as $option)
                            <fieldset>
                                <legend class="mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $option->name }}
                                </legend>
                                @if($option->values->count() <= 6)
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($option->values->sortBy('position') as $value)
                                            <label class="cursor-pointer">
                                                <input type="radio"
                                                       wire:model.live="selectedOptions.{{ $option->name }}"
                                                       value="{{ $value->value }}"
                                                       class="peer sr-only"
                                                       name="option_{{ $option->id }}">
                                                <span class="inline-flex items-center rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors peer-checked:border-blue-600 peer-checked:bg-blue-50 peer-checked:text-blue-700 peer-focus-visible:ring-2 peer-focus-visible:ring-blue-500 dark:border-gray-600 dark:text-gray-300 dark:peer-checked:border-blue-500 dark:peer-checked:bg-blue-900/20 dark:peer-checked:text-blue-400">
                                                    {{ $value->value }}
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                @else
                                    <select wire:model.live="selectedOptions.{{ $option->name }}"
                                            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                        @foreach($option->values->sortBy('position') as $value)
                                            <option value="{{ $value->value }}">{{ $value->value }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            </fieldset>
                        @endforeach
                    </div>
                @endif

                {{-- Stock messaging --}}
                @if($selectedVariant)
                    @php
                        $inventory = $selectedVariant->inventoryItem;
                        $available = $inventory ? $inventory->quantity_on_hand - $inventory->quantity_reserved : 0;
                    @endphp
                    <div class="mt-4" aria-live="polite">
                        @if($inventory)
                            @if($available > 10)
                                <p class="flex items-center gap-1.5 text-sm text-green-600 dark:text-green-400">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                    </svg>
                                    In stock
                                </p>
                            @elseif($available > 0)
                                <p class="flex items-center gap-1.5 text-sm text-amber-600 dark:text-amber-400">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                    </svg>
                                    Only {{ $available }} left in stock
                                </p>
                            @elseif($inventory->policy === 'deny')
                                <p class="flex items-center gap-1.5 text-sm text-red-600 dark:text-red-400">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                    </svg>
                                    Out of stock
                                </p>
                            @else
                                <p class="flex items-center gap-1.5 text-sm text-blue-600 dark:text-blue-400">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                                    </svg>
                                    Available on backorder
                                </p>
                            @endif
                        @endif
                    </div>
                @endif

                {{-- Quantity and add to cart --}}
                <div class="mt-6 space-y-4">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Quantity</label>
                        <x-storefront.components.quantity-selector
                            :value="$quantity"
                            :min="1"
                            :max="isset($inventory) && $inventory->policy === 'deny' ? max(1, $available) : null"
                            wire-model="quantity"
                        />
                    </div>

                    @php
                        $isSoldOut = isset($inventory) && $inventory->policy === 'deny' && $available <= 0;
                    @endphp

                    <button wire:click="addToCart"
                            wire:loading.attr="disabled"
                            @if($isSoldOut) disabled @endif
                            class="w-full rounded-md px-6 py-3 text-base font-semibold text-white shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed {{ $isSoldOut ? 'bg-gray-400 dark:bg-gray-600' : 'bg-blue-600 hover:bg-blue-700' }}">
                        <span wire:loading.remove>
                            {{ $isSoldOut ? 'Sold out' : 'Add to cart' }}
                        </span>
                        <span wire:loading>Adding...</span>
                    </button>
                </div>

                {{-- Description --}}
                @if($product->description_html)
                    <div class="mt-8 border-t border-gray-200 pt-8 dark:border-gray-800">
                        <div class="prose max-w-none text-gray-600 dark:prose-invert dark:text-gray-400">
                            {!! strip_tags($product->description_html, '<p><br><strong><em><ul><ol><li><a><h2><h3><h4><blockquote>') !!}
                        </div>
                    </div>
                @endif

                {{-- Tags --}}
                @php
                    $tags = is_string($product->tags) ? json_decode($product->tags, true) : ($product->tags ?? []);
                @endphp
                @if(!empty($tags))
                    <div class="mt-6 flex flex-wrap gap-2">
                        @foreach($tags as $tag)
                            <span class="rounded-full bg-gray-100 px-3 py-1 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                {{ $tag }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
