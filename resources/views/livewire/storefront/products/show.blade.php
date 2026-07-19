<div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    @php
        /** @var \App\Models\Product $product */
        $variant = $this->selectedVariant();
        $images = $product->media->filter(fn (\App\Models\ProductMedia $media): bool => $media->status === \App\Enums\MediaStatus::Ready)->values();
        $primaryCollection = $product->collections->first();
        $breadcrumbs = [['label' => 'Home', 'url' => route('storefront.home')]];
        if ($primaryCollection !== null) {
            $breadcrumbs[] = ['label' => $primaryCollection->title, 'url' => route('storefront.collections.show', ['handle' => $primaryCollection->handle])];
        }
        $breadcrumbs[] = ['label' => $product->title];
        $onSale = $variant !== null && $variant->compare_at_amount !== null && $variant->compare_at_amount > $variant->price_amount;

        $stockStyles = [
            'in_stock' => 'text-green-600 dark:text-green-400',
            'low_stock' => 'text-amber-600 dark:text-amber-400',
            'out_of_stock' => 'text-red-600 dark:text-red-400',
            'backorder' => 'text-blue-600 dark:text-blue-400',
            'unavailable' => 'text-gray-500 dark:text-gray-400',
        ];

        $swatchColors = [
            'black' => '#111827', 'white' => '#f9fafb', 'gray' => '#6b7280', 'grey' => '#6b7280',
            'red' => '#dc2626', 'orange' => '#ea580c', 'amber' => '#d97706', 'yellow' => '#eab308',
            'green' => '#16a34a', 'teal' => '#0d9488', 'blue' => '#2563eb', 'navy' => '#1e3a8a',
            'purple' => '#9333ea', 'pink' => '#db2777', 'brown' => '#92400e', 'beige' => '#d6c9b0',
        ];
    @endphp

    <x-storefront::breadcrumbs :items="$breadcrumbs" />

    <div class="mt-8 grid grid-cols-1 gap-10 lg:grid-cols-2">
        {{-- Image gallery --}}
        <section aria-label="Product images" x-data="{ active: 0 }">
            {{-- Desktop: main image + thumbnails --}}
            <div class="hidden lg:block">
                <div class="aspect-square overflow-hidden rounded-xl bg-gray-100 dark:bg-gray-800">
                    @if ($images->isNotEmpty())
                        @foreach ($images as $index => $image)
                            <img src="{{ $image->url() }}"
                                 x-show="active === {{ $index }}"
                                 alt="{{ $image->alt_text ?? $product->title }}"
                                 class="size-full object-cover object-center">
                        @endforeach
                    @else
                        <span class="flex size-full items-center justify-center text-gray-300 dark:text-gray-600">
                            <svg class="size-24" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z" />
                            </svg>
                        </span>
                    @endif
                </div>
                @if ($images->count() > 1)
                    <div class="mt-4 flex gap-3 overflow-x-auto" role="group" aria-label="Image thumbnails">
                        @foreach ($images as $index => $image)
                            <button type="button"
                                    @click="active = {{ $index }}"
                                    :aria-current="active === {{ $index }} ? 'true' : 'false'"
                                    :class="active === {{ $index }} ? 'ring-2 ring-blue-600' : 'ring-1 ring-gray-200 dark:ring-gray-700'"
                                    class="size-16 shrink-0 overflow-hidden rounded-md focus:outline-hidden focus:ring-2 focus:ring-blue-500"
                                    aria-label="View image {{ $index + 1 }} of {{ $images->count() }}">
                                <img src="{{ $image->url() }}" alt="" loading="lazy" class="size-full object-cover object-center">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Mobile: snap-scroll gallery with dots --}}
            <div class="lg:hidden" x-data="{ activeMobile: 0 }">
                <div class="flex snap-x snap-mandatory gap-4 overflow-x-auto"
                     x-on:scroll.debounce.100ms="activeMobile = Math.round($el.scrollLeft / $el.clientWidth)">
                    @forelse ($images as $image)
                        <div class="aspect-square w-full shrink-0 snap-center overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-800">
                            <img src="{{ $image->url() }}" alt="{{ $image->alt_text ?? $product->title }}" loading="lazy" class="size-full object-cover object-center">
                        </div>
                    @empty
                        <div class="flex aspect-square w-full items-center justify-center rounded-lg bg-gray-100 text-gray-300 dark:bg-gray-800 dark:text-gray-600">
                            <svg class="size-24" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z" />
                            </svg>
                        </div>
                    @endforelse
                </div>
                @if ($images->count() > 1)
                    <div class="mt-3 flex justify-center gap-2" aria-hidden="true">
                        @foreach ($images as $index => $image)
                            <span :class="activeMobile === {{ $index }} ? 'bg-gray-900 dark:bg-white' : 'bg-gray-300 dark:bg-gray-600'" class="size-2 rounded-full"></span>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        {{-- Product info --}}
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl dark:text-white">{{ $product->title }}</h1>

            @if (! empty($product->vendor))
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $product->vendor }}</p>
            @endif

            <div class="mt-4 flex items-center gap-3" aria-live="polite">
                @if ($variant !== null)
                    <span class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ \App\Support\Money::format($variant->price_amount, $currency) }}
                    </span>
                    @if ($onSale)
                        <s class="text-lg text-gray-500 dark:text-gray-400">{{ \App\Support\Money::format($variant->compare_at_amount, $currency) }}</s>
                        <x-storefront::badge text="Sale" variant="sale" />
                    @endif
                @else
                    <span class="text-lg text-gray-500 dark:text-gray-400">Unavailable</span>
                @endif
            </div>

            {{-- Variant selectors --}}
            @foreach ($product->options as $option)
                <fieldset class="mt-6" wire:key="option-{{ $option->id }}">
                    <legend class="text-sm font-semibold text-gray-900 dark:text-white">
                        {{ $option->name }}@if (! empty($selectedOptions[$option->name]))<span class="ml-2 font-normal text-gray-500 dark:text-gray-400">{{ $selectedOptions[$option->name] }}</span>@endif
                    </legend>
                    @if ($option->name === 'Color')
                        <div class="mt-3 flex flex-wrap gap-3">
                            @foreach ($option->values as $value)
                                @php $available = $this->isValueAvailable($option, $value->value); @endphp
                                <button type="button"
                                        wire:click="selectOption(@js($option->name), @js($value->value))"
                                        @disabled(! $available)
                                        title="{{ $value->value }}"
                                        aria-label="{{ $value->value }}{{ $available ? '' : ' (unavailable)' }}"
                                        aria-pressed="{{ ($selectedOptions[$option->name] ?? null) === $value->value ? 'true' : 'false' }}"
                                        class="size-8 rounded-full border border-gray-300 transition focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-40 dark:border-gray-600 dark:focus:ring-offset-gray-950 {{ ($selectedOptions[$option->name] ?? null) === $value->value ? 'ring-2 ring-blue-600 ring-offset-2 dark:ring-offset-gray-950' : '' }}"
                                        style="background-color: {{ $swatchColors[mb_strtolower($value->value)] ?? '#9ca3af' }}">
                                </button>
                            @endforeach
                        </div>
                    @elseif ($option->values->count() <= 6)
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($option->values as $value)
                                @php $available = $this->isValueAvailable($option, $value->value); @endphp
                                <button type="button"
                                        wire:click="selectOption(@js($option->name), @js($value->value))"
                                        @disabled(! $available)
                                        aria-pressed="{{ ($selectedOptions[$option->name] ?? null) === $value->value ? 'true' : 'false' }}"
                                        class="rounded-md border px-4 py-2 text-sm font-medium transition focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:border-gray-200 disabled:text-gray-400 disabled:line-through disabled:opacity-60 dark:focus:ring-offset-gray-950 dark:disabled:border-gray-800 {{ ($selectedOptions[$option->name] ?? null) === $value->value
                                            ? 'border-blue-600 bg-blue-50 text-blue-700 dark:border-blue-400 dark:bg-blue-950 dark:text-blue-300'
                                            : 'border-gray-300 text-gray-700 hover:border-gray-400 dark:border-gray-700 dark:text-gray-200 dark:hover:border-gray-500' }}">
                                    {{ $value->value }}
                                </button>
                            @endforeach
                        </div>
                    @else
                        <label for="option-{{ $option->id }}" class="sr-only">{{ $option->name }}</label>
                        <select id="option-{{ $option->id }}"
                                wire:change="selectOption(@js($option->name), $event.target.value)"
                                class="mt-3 w-full max-w-xs rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-hidden focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            @foreach ($option->values as $value)
                                <option value="{{ $value->value }}" @selected(($selectedOptions[$option->name] ?? null) === $value->value)>
                                    {{ $value->value }}
                                </option>
                            @endforeach
                        </select>
                    @endif
                </fieldset>
            @endforeach

            {{-- Stock messaging --}}
            <p class="mt-4 flex items-center gap-1.5 text-sm font-medium {{ $stockStyles[$stock['state']] }}" aria-live="polite">
                @if ($stock['state'] === 'in_stock')
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                @elseif ($stock['state'] === 'low_stock')
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z" /></svg>
                @elseif ($stock['state'] === 'out_of_stock')
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                @else
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12v-.008z" /></svg>
                @endif
                {{ $stock['message'] }}
            </p>

            {{-- Quantity + add to cart --}}
            <div class="mt-6 flex flex-col gap-4">
                @if ($stock['purchasable'])
                    <x-storefront::quantity-selector :value="$quantity" :max="$stock['max']" wireModel="quantity" />
                @endif
                <button type="button"
                        wire:click="addToCart"
                        @disabled(! $stock['purchasable'])
                        wire:loading.attr="disabled"
                        class="w-full rounded-md px-6 py-3 text-base font-semibold transition focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed dark:focus:ring-offset-gray-950 {{ $stock['purchasable']
                            ? 'bg-blue-600 text-white hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-400'
                            : 'bg-gray-200 text-gray-500 dark:bg-gray-800 dark:text-gray-400' }}">
                    <span wire:loading.remove wire:target="addToCart">{{ $stock['purchasable'] ? 'Add to cart' : ($stock['state'] === 'out_of_stock' ? 'Sold out' : 'Unavailable') }}</span>
                    <span wire:loading wire:target="addToCart">Adding...</span>
                </button>
            </div>

            {{-- Description --}}
            @if (! empty($product->description_html))
                <hr class="my-8 border-gray-200 dark:border-gray-800">
                <div class="storefront-prose text-gray-700 dark:text-gray-300">
                    {!! $product->description_html !!}
                </div>
            @endif

            {{-- Tags --}}
            @if (! empty($product->tags))
                <div class="mt-6 flex flex-wrap gap-2">
                    @foreach ($product->tags as $tag)
                        <span class="rounded-full bg-gray-100 px-3 py-1 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-300">{{ $tag }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <script type="application/ld+json">@json($jsonLd)</script>
</div>
