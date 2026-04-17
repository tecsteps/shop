<div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
        {{-- Breadcrumbs --}}
        @php
            $breadcrumbItems = [['label' => 'Home', 'url' => route('storefront.home')]];
            $primaryCollection = $product->collections->first();
            if ($primaryCollection) {
                $breadcrumbItems[] = ['label' => $primaryCollection->title, 'url' => route('storefront.collections.show', $primaryCollection->handle)];
            }
            $breadcrumbItems[] = ['label' => $product->title];
        @endphp
        <x-storefront.breadcrumbs :items="$breadcrumbItems" class="mb-6" />

        <div class="lg:grid lg:grid-cols-2 lg:gap-12">
            {{-- Image Gallery --}}
            <div class="lg:sticky lg:top-24 lg:self-start mb-8 lg:mb-0">
                @php
                    $images = $product->media->sortBy('position');
                    $mainImage = $images->first();
                @endphp

                @if ($mainImage)
                    <div class="aspect-square overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800 mb-4" x-data="{ activeImage: '{{ $mainImage->url }}' }">
                        <img
                            :src="activeImage"
                            alt="{{ $mainImage->alt_text ?: $product->title }}"
                            class="size-full object-cover"
                        />
                    </div>

                    @if ($images->count() > 1)
                        <div class="flex gap-2 overflow-x-auto pb-2" x-data="{ activeIndex: 0 }">
                            @foreach ($images as $index => $image)
                                <button
                                    type="button"
                                    @click="activeIndex = {{ $index }}; $el.closest('[x-data]').parentElement.previousElementSibling.querySelector('img').src = '{{ $image->url }}'"
                                    class="shrink-0 size-16 rounded-lg overflow-hidden border-2 transition-colors"
                                    :class="activeIndex === {{ $index }} ? 'border-blue-500' : 'border-transparent hover:border-zinc-300 dark:hover:border-zinc-600'"
                                    aria-label="View image {{ $index + 1 }} of {{ $images->count() }}"
                                >
                                    <img
                                        src="{{ $image->url }}"
                                        alt="{{ $image->alt_text ?: "Product image " . ($index + 1) }}"
                                        class="size-full object-cover"
                                        loading="lazy"
                                    />
                                </button>
                            @endforeach
                        </div>
                    @endif
                @else
                    <div class="aspect-square overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center">
                        <flux:icon name="shopping-bag" class="size-24 text-zinc-300 dark:text-zinc-600" />
                    </div>
                @endif
            </div>

            {{-- Product Info --}}
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-zinc-900 dark:text-white">
                    {{ $product->title }}
                </h1>

                {{-- Price --}}
                @if ($selectedVariant)
                    <div class="mt-4 flex items-center gap-3" aria-live="polite">
                        <x-storefront.price
                            :amount="$selectedVariant->price_amount"
                            :currency="$currency"
                            :compare-at="$selectedVariant->compare_at_price_amount"
                            class="text-xl"
                        />
                        @if ($selectedVariant->compare_at_price_amount && $selectedVariant->compare_at_price_amount > $selectedVariant->price_amount)
                            <x-storefront.badge variant="sale">Sale</x-storefront.badge>
                        @endif
                    </div>
                @endif

                {{-- Variant Selector --}}
                @if ($product->options->count() > 0 && $product->variants->count() > 1)
                    <div class="mt-6 space-y-4">
                        @foreach ($product->options as $option)
                            <fieldset>
                                <legend class="text-sm font-medium text-zinc-900 dark:text-white mb-2">{{ $option->name }}</legend>
                                @if ($option->values->count() <= 6)
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($option->values as $optionValue)
                                            <label
                                                class="cursor-pointer"
                                                wire:key="option-{{ $option->id }}-{{ $optionValue->id }}"
                                            >
                                                <input
                                                    type="radio"
                                                    name="option_{{ $option->name }}"
                                                    value="{{ $optionValue->value }}"
                                                    wire:model.live="selectedOptions.{{ $option->name }}"
                                                    class="sr-only peer"
                                                />
                                                <span class="inline-flex items-center px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-full text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:border-zinc-500 dark:hover:border-zinc-400 peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-500/10 peer-checked:text-blue-600 dark:peer-checked:text-blue-400 transition-colors">
                                                    {{ $optionValue->value }}
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                @else
                                    <select
                                        wire:model.live="selectedOptions.{{ $option->name }}"
                                        class="w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    >
                                        @foreach ($option->values as $optionValue)
                                            <option value="{{ $optionValue->value }}">{{ $optionValue->value }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            </fieldset>
                        @endforeach
                    </div>
                @endif

                {{-- Stock Messaging --}}
                @if ($selectedVariant)
                    @php
                        $inventory = $selectedVariant->inventoryItem;
                        $available = $inventory ? $inventory->quantityAvailable() : null;
                        $policy = $inventory?->policy;
                    @endphp
                    <div class="mt-4" aria-live="polite">
                        @if ($available === null || $available > 10)
                            <p class="text-sm text-green-600 dark:text-green-400 flex items-center gap-1.5">
                                <flux:icon name="check-circle" class="size-4" />
                                In stock
                            </p>
                        @elseif ($available > 0)
                            <p class="text-sm text-amber-600 dark:text-amber-400 flex items-center gap-1.5">
                                <flux:icon name="exclamation-triangle" class="size-4" />
                                Only {{ $available }} left in stock
                            </p>
                        @elseif ($policy === \App\Enums\InventoryPolicy::Continue)
                            <p class="text-sm text-blue-600 dark:text-blue-400 flex items-center gap-1.5">
                                <flux:icon name="information-circle" class="size-4" />
                                Available on backorder
                            </p>
                        @else
                            <p class="text-sm text-red-600 dark:text-red-400 flex items-center gap-1.5">
                                <flux:icon name="x-circle" class="size-4" />
                                Out of stock
                            </p>
                        @endif
                    </div>
                @endif

                {{-- Quantity + Add to Cart --}}
                <div class="mt-6 space-y-4">
                    @php
                        $isSoldOut = $selectedVariant
                            && $selectedVariant->inventoryItem
                            && $selectedVariant->inventoryItem->policy === \App\Enums\InventoryPolicy::Deny
                            && $selectedVariant->inventoryItem->quantityAvailable() <= 0;
                    @endphp

                    <x-storefront.quantity-selector
                        wire-model="quantity"
                        :disabled="$isSoldOut"
                    />

                    <flux:button
                        wire:click="addToCart"
                        variant="primary"
                        class="w-full justify-center py-3"
                        :disabled="$isSoldOut || !$selectedVariant"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove wire:target="addToCart">
                            {{ $isSoldOut ? 'Sold out' : 'Add to cart' }}
                        </span>
                        <span wire:loading wire:target="addToCart">
                            Adding...
                        </span>
                    </flux:button>
                </div>

                {{-- Description --}}
                @if ($product->description_html)
                    <div class="mt-8 pt-8 border-t border-zinc-200 dark:border-zinc-700">
                        <div class="prose dark:prose-invert max-w-none">
                            {!! $product->description_html !!}
                        </div>
                    </div>
                @endif

                {{-- Tags --}}
                @if (is_array($product->tags) && count($product->tags) > 0)
                    <div class="mt-6 flex flex-wrap gap-2">
                        @foreach ($product->tags as $tag)
                            <span class="inline-block px-3 py-1 text-xs bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 rounded-full">
                                {{ $tag }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
