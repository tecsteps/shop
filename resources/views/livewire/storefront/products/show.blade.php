<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
    @php
        $firstCollection = $product->collections->first();
    @endphp
    <x-storefront.breadcrumbs :items="array_filter([
        ['label' => 'Home', 'url' => route('storefront.home')],
        $firstCollection ? ['label' => $firstCollection->title, 'url' => route('storefront.collections.show', $firstCollection->handle)] : null,
        ['label' => $product->title, 'url' => ''],
    ])" />

    <div class="lg:grid lg:grid-cols-2 lg:gap-12">
        {{-- Image Gallery --}}
        <div class="lg:sticky lg:top-24 lg:self-start mb-8 lg:mb-0">
            @php
                $media = $product->media->sortBy('position');
                $mainImage = $media->first();
            @endphp
            <div class="aspect-square bg-gray-100 dark:bg-gray-800 rounded-xl overflow-hidden">
                @if($mainImage)
                    <img src="{{ $mainImage->storage_key }}"
                         alt="{{ $mainImage->alt_text ?? $product->title }}"
                         class="w-full h-full object-cover">
                @else
                    <div class="w-full h-full flex items-center justify-center text-gray-300 dark:text-gray-600">
                        <svg class="w-24 h-24" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    </div>
                @endif
            </div>
            @if($media->count() > 1)
                <div class="flex gap-2 mt-3 overflow-x-auto">
                    @foreach($media as $item)
                        <div class="w-16 h-16 flex-shrink-0 rounded-lg overflow-hidden border-2 {{ $loop->first ? 'border-blue-500' : 'border-gray-200 dark:border-gray-700' }}">
                            <img src="{{ $item->storage_key }}" alt="{{ $item->alt_text ?? '' }}" class="w-full h-full object-cover" loading="lazy">
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Product Info --}}
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 dark:text-white">{{ $product->title }}</h1>

            {{-- Price --}}
            <div class="mt-4 flex items-center gap-3" aria-live="polite">
                @if($selectedVariant)
                    <span class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ \App\Support\MoneyFormatter::format($selectedVariant->price_amount, $selectedVariant->currency ?? 'EUR') }}
                    </span>
                    @if($selectedVariant->compare_at_amount && $selectedVariant->compare_at_amount > $selectedVariant->price_amount)
                        <span class="text-lg text-gray-500 line-through">
                            {{ \App\Support\MoneyFormatter::format($selectedVariant->compare_at_amount, $selectedVariant->currency ?? 'EUR') }}
                        </span>
                        <span class="bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300 text-xs font-medium px-2 py-0.5 rounded-full">Sale</span>
                    @endif
                @endif
            </div>

            {{-- Variant Selectors --}}
            @if($product->options->isNotEmpty() && !($product->variants->count() === 1 && $product->variants->first()->is_default))
                <div class="mt-6 space-y-4">
                    @foreach($product->options as $option)
                        <fieldset>
                            <legend class="text-sm font-medium text-gray-900 dark:text-white mb-2">{{ $option->name }}</legend>
                            <div class="flex flex-wrap gap-2">
                                @foreach($option->values->sortBy('position') as $value)
                                    <label class="relative cursor-pointer">
                                        <input type="radio"
                                               wire:model.live="selectedOptions.{{ $option->name }}"
                                               value="{{ $value->value }}"
                                               class="peer sr-only"
                                               name="option_{{ $option->id }}">
                                        <span class="inline-flex items-center px-4 py-2 border-2 rounded-lg text-sm font-medium transition-colors
                                                     peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20 peer-checked:text-blue-700 dark:peer-checked:text-blue-300
                                                     border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-gray-400 dark:hover:border-gray-500">
                                            {{ $value->value }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endforeach
                </div>
            @endif

            {{-- Stock Status --}}
            <div class="mt-4" aria-live="polite">
                @php
                    $statusColors = [
                        'success' => 'text-green-600 dark:text-green-400',
                        'warning' => 'text-amber-600 dark:text-amber-400',
                        'error' => 'text-red-600 dark:text-red-400',
                        'info' => 'text-blue-600 dark:text-blue-400',
                    ];
                    $statusIcons = [
                        'success' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>',
                        'warning' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/>',
                        'error' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>',
                        'info' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                    ];
                @endphp
                <span class="inline-flex items-center gap-1.5 text-sm {{ $statusColors[$stockStatus['type']] ?? '' }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">{!! $statusIcons[$stockStatus['type']] ?? '' !!}</svg>
                    {{ $stockStatus['message'] }}
                </span>
            </div>

            {{-- Quantity + Add to Cart --}}
            <div class="mt-6 space-y-4">
                {{-- Quantity Selector --}}
                <div class="inline-flex items-center border border-gray-300 dark:border-gray-600 rounded-lg">
                    <button type="button" wire:click="decrementQuantity"
                            class="w-10 h-10 flex items-center justify-center text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white disabled:opacity-50 disabled:cursor-not-allowed"
                            @if($quantity <= 1) disabled @endif
                            aria-label="Decrease quantity">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                    </button>
                    <input type="number" wire:model="quantity" min="1"
                           class="w-14 h-10 text-center border-x border-gray-300 dark:border-gray-600 bg-transparent text-sm font-medium text-gray-900 dark:text-white focus:outline-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                           aria-label="Quantity">
                    <button type="button" wire:click="incrementQuantity"
                            class="w-10 h-10 flex items-center justify-center text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"
                            aria-label="Increase quantity">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </button>
                </div>

                {{-- Add to Cart --}}
                @if($stockStatus['type'] === 'error')
                    <button disabled class="w-full py-3 px-6 bg-gray-300 dark:bg-gray-700 text-gray-500 dark:text-gray-400 font-semibold rounded-lg cursor-not-allowed">
                        Sold out
                    </button>
                @else
                    <button wire:click="addToCart"
                            wire:loading.attr="disabled"
                            class="w-full py-3 px-6 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white font-semibold rounded-lg transition-colors">
                        <span wire:loading.remove wire:target="addToCart">Add to cart</span>
                        <span wire:loading wire:target="addToCart">Adding...</span>
                    </button>
                @endif
            </div>

            {{-- Description --}}
            @if($product->description_html)
                <hr class="my-8 border-gray-200 dark:border-gray-800">
                <div class="prose prose-sm dark:prose-invert max-w-none">
                    {!! $product->description_html !!}
                </div>
            @endif

            {{-- Tags --}}
            @if(!empty($product->tags))
                <div class="mt-6 flex flex-wrap gap-2">
                    @foreach($product->tags as $tag)
                        <span class="inline-flex items-center px-3 py-1 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 text-xs rounded-full">
                            {{ $tag }}
                        </span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
