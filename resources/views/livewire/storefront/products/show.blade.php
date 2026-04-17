<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    {{-- Breadcrumbs --}}
    <x-storefront.breadcrumbs :items="array_filter([
        ['label' => 'Home', 'url' => route('storefront.home')],
        $primaryCollection ? ['label' => $primaryCollection->title, 'url' => route('storefront.collections.show', $primaryCollection->handle)] : null,
        ['label' => $product->title],
    ])" />

    <div class="mt-6 lg:grid lg:grid-cols-2 lg:gap-12">
        {{-- Image Gallery --}}
        <div class="lg:sticky lg:top-24 lg:self-start" x-data="{ activeImage: 0 }">
            @if($product->media->isNotEmpty())
                {{-- Main Image --}}
                <div class="aspect-square overflow-hidden rounded-xl bg-zinc-100 dark:bg-zinc-800">
                    @foreach($product->media->sortBy('position') as $index => $media)
                        <img x-show="activeImage === {{ $index }}"
                             src="{{ $media->storage_key }}"
                             alt="{{ $media->alt_text ?? $product->title }}"
                             class="h-full w-full object-cover">
                    @endforeach
                </div>

                {{-- Thumbnails --}}
                @if($product->media->count() > 1)
                    <div class="mt-4 flex gap-3 overflow-x-auto">
                        @foreach($product->media->sortBy('position') as $index => $media)
                            <button @click="activeImage = {{ $index }}"
                                    class="h-16 w-16 flex-shrink-0 overflow-hidden rounded-lg border-2 transition-colors"
                                    :class="activeImage === {{ $index }} ? 'border-blue-500' : 'border-zinc-200 dark:border-zinc-700'"
                                    aria-label="View image {{ $index + 1 }} of {{ $product->media->count() }}">
                                <img src="{{ $media->storage_key }}"
                                     alt=""
                                     class="h-full w-full object-cover">
                            </button>
                        @endforeach
                    </div>
                @endif
            @else
                <div class="flex aspect-square items-center justify-center rounded-xl bg-zinc-100 dark:bg-zinc-800">
                    <svg class="h-24 w-24 text-zinc-300 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                    </svg>
                </div>
            @endif
        </div>

        {{-- Product Info --}}
        <div class="mt-8 lg:mt-0">
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white lg:text-3xl">{{ $product->title }}</h1>

            {{-- Price --}}
            @if($selectedVariant)
                <div class="mt-4" aria-live="polite">
                    <x-storefront.price
                        :amount="$selectedVariant->price_amount"
                        :currency="$selectedVariant->currency"
                        :compare-at-amount="$selectedVariant->compare_at_amount"
                        class="text-xl" />
                    @if($selectedVariant->compare_at_amount && $selectedVariant->compare_at_amount > $selectedVariant->price_amount)
                        <x-storefront.badge text="Sale" variant="sale" class="ml-2" />
                    @endif
                </div>

                {{-- Stock Messaging --}}
                <div class="mt-3" aria-live="polite">
                    @php
                        $inventory = $selectedVariant->inventoryItem;
                        $available = $inventory ? ($inventory->quantity_on_hand - $inventory->quantity_reserved) : null;
                    @endphp
                    @if($inventory)
                        @if($available > 10)
                            <span class="inline-flex items-center gap-1 text-sm text-green-600 dark:text-green-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                                In stock
                            </span>
                        @elseif($available > 0)
                            <span class="inline-flex items-center gap-1 text-sm text-amber-600 dark:text-amber-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                </svg>
                                Only {{ $available }} left in stock
                            </span>
                        @elseif($inventory->policy === 'deny')
                            <span class="inline-flex items-center gap-1 text-sm text-red-600 dark:text-red-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Out of stock
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 text-sm text-blue-600 dark:text-blue-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                                </svg>
                                Available on backorder
                            </span>
                        @endif
                    @endif
                </div>
            @endif

            {{-- Quantity & Add to Cart --}}
            <div class="mt-6">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Quantity</label>
                <div class="mt-2">
                    <x-storefront.quantity-selector :value="$quantity" wire-model="quantity" />
                </div>
            </div>

            @php
                $isSoldOut = $selectedVariant
                    && $selectedVariant->inventoryItem
                    && $selectedVariant->inventoryItem->policy === 'deny'
                    && ($selectedVariant->inventoryItem->quantity_on_hand - $selectedVariant->inventoryItem->quantity_reserved) <= 0;
            @endphp

            <button wire:click="addToCart"
                    wire:loading.attr="disabled"
                    @if($isSoldOut) disabled @endif
                    class="mt-6 w-full rounded-md px-6 py-3 text-base font-semibold transition-colors
                        {{ $isSoldOut
                            ? 'bg-zinc-300 text-zinc-500 cursor-not-allowed dark:bg-zinc-700 dark:text-zinc-500'
                            : 'bg-blue-600 text-white hover:bg-blue-700' }}">
                <span wire:loading.remove>{{ $isSoldOut ? 'Sold out' : 'Add to cart' }}</span>
                <span wire:loading>Adding...</span>
            </button>

            {{-- Description --}}
            @if($product->description_html)
                <div class="mt-8 border-t border-zinc-200 dark:border-zinc-700 pt-8">
                    <div class="prose dark:prose-invert max-w-none">
                        {!! $product->description_html !!}
                    </div>
                </div>
            @endif

            {{-- Tags --}}
            @if(!empty($product->tags) && count($product->tags) > 0)
                <div class="mt-6 flex flex-wrap gap-2">
                    @foreach($product->tags as $tag)
                        <span class="rounded-full bg-zinc-100 dark:bg-zinc-800 px-3 py-1 text-sm text-zinc-600 dark:text-zinc-400">
                            {{ $tag }}
                        </span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
