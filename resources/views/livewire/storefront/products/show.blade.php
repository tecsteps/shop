@php
    use App\Support\Storefront\PriceFormatter;
@endphp

<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8" x-data="{ activeImage: 0 }">
    <x-storefront::breadcrumbs :items="[
        ['label' => __('Home'), 'url' => route('storefront.home')],
        ['label' => __('Collections'), 'url' => '/collections'],
        ['label' => $product->title],
    ]" />

    <div class="mt-6 grid grid-cols-1 gap-10 lg:grid-cols-2">
        {{-- Image gallery. --}}
        <div class="lg:sticky lg:top-24 lg:self-start" role="region" aria-label="{{ __('Product images') }}">
            <div class="aspect-square overflow-hidden rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                @if ($media->isNotEmpty())
                    @foreach ($media as $index => $image)
                        <img x-show="activeImage === {{ $index }}" x-cloak="{{ $index > 0 ? 'true' : 'false' }}"
                             src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($image->storage_key) }}"
                             alt="{{ $image->alt_text ?? $product->title }}"
                             class="h-full w-full object-cover" @if ($index > 0) style="display:none" @endif />
                    @endforeach
                @else
                    <div class="flex h-full w-full items-center justify-center text-zinc-300 dark:text-zinc-600" aria-hidden="true">
                        <flux:icon.shopping-bag class="size-20" />
                    </div>
                @endif
            </div>

            @if ($media->count() > 1)
                <div class="mt-4 flex gap-3 overflow-x-auto">
                    @foreach ($media as $index => $image)
                        <button type="button" x-on:click="activeImage = {{ $index }}"
                                x-bind:class="activeImage === {{ $index }} ? 'border-blue-600' : 'border-transparent'"
                                class="size-16 shrink-0 overflow-hidden rounded-lg border-2"
                                aria-label="{{ __('View image :n of :total', ['n' => $index + 1, 'total' => $media->count()]) }}">
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($image->storage_key) }}"
                                 alt="" class="h-full w-full object-cover" />
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Product info. --}}
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ $product->title }}</h1>

            <div class="mt-4" aria-live="polite">
                <x-storefront::price :amount="$priceAmount" :currency="$currency" :compare-at-amount="$compareAtAmount" class="text-2xl" />
            </div>

            {{-- Variant selectors. --}}
            @if ($options->isNotEmpty())
                <div class="mt-6 space-y-5">
                    @foreach ($options as $option)
                        <fieldset>
                            <legend class="text-sm font-medium text-zinc-900 dark:text-white">{{ $option['name'] }}</legend>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach ($option['values'] as $value)
                                    @php $isSelected = ($selectedOptions[$option['name']] ?? null) === $value; @endphp
                                    <label class="cursor-pointer">
                                        <input type="radio"
                                               wire:model.live="selectedOptions.{{ $option['name'] }}"
                                               value="{{ $value }}"
                                               class="peer sr-only" />
                                        <span class="inline-flex items-center rounded-lg border px-4 py-2 text-sm transition
                                                     @if ($isSelected) border-blue-600 bg-blue-50 text-blue-700 ring-1 ring-blue-600 dark:bg-blue-950 dark:text-blue-300 @else border-zinc-300 text-zinc-700 hover:border-zinc-900 dark:border-zinc-700 dark:text-zinc-200 dark:hover:border-white @endif">
                                            {{ $value }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endforeach
                </div>
            @endif

            {{-- Stock messaging. --}}
            <div class="mt-6 flex items-center gap-2 text-sm" aria-live="polite">
                @php
                    $toneClasses = [
                        'green' => 'text-green-600 dark:text-green-400',
                        'amber' => 'text-amber-600 dark:text-amber-400',
                        'blue' => 'text-blue-600 dark:text-blue-400',
                        'red' => 'text-red-600 dark:text-red-400',
                    ][$stock['tone']] ?? 'text-zinc-600';
                    $toneIcon = ['green' => 'check-circle', 'amber' => 'exclamation-triangle', 'blue' => 'information-circle', 'red' => 'x-circle'][$stock['tone']] ?? 'information-circle';
                @endphp
                <flux:icon :icon="$toneIcon" class="size-5 {{ $toneClasses }}" />
                <span class="{{ $toneClasses }}">{{ $stock['label'] }}</span>
            </div>

            {{-- Quantity + add to cart. --}}
            <div class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-center">
                @if ($stock['purchasable'])
                    <x-storefront::quantity-selector wire-model="quantity" :value="$quantity" :max="$maxQuantity" />
                @endif

                <button type="button" wire:click="addToCart" wire:loading.attr="disabled"
                        @disabled(! $stock['purchasable'])
                        class="flex-1 rounded-lg px-6 py-3 text-base font-semibold text-white transition
                               @if ($stock['purchasable']) bg-blue-600 hover:bg-blue-700 @else cursor-not-allowed bg-zinc-300 text-zinc-500 dark:bg-zinc-700 @endif">
                    <span wire:loading.remove wire:target="addToCart">{{ $stock['purchasable'] ? __('Add to cart') : __('Sold out') }}</span>
                    <span wire:loading wire:target="addToCart">{{ __('Adding...') }}</span>
                </button>
            </div>

            {{-- Description. --}}
            @if ($product->description_html)
                <hr class="my-8 border-zinc-200 dark:border-zinc-800" />
                <div class="prose prose-zinc max-w-none dark:prose-invert">
                    {!! $product->description_html !!}
                </div>
            @endif

            {{-- Tags. --}}
            @if (! empty($tags))
                <div class="mt-6 flex flex-wrap gap-2">
                    @foreach ($tags as $tag)
                        <x-storefront::badge :text="$tag" />
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
