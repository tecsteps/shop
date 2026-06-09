@php
    use App\Enums\InventoryPolicy;
    use App\Enums\MediaStatus;
    use Illuminate\Support\Facades\Storage;

    $galleryMedia = $product->media
        ->filter(fn ($media): bool => $media->status === MediaStatus::Ready || $media->status === MediaStatus::Processing)
        ->values();
    $activeMedia = $galleryMedia->get($activeImageIndex) ?? $galleryMedia->first();

    $priceAmount = $selectedVariant?->price_amount ?? 0;
    $compareAtAmount = $selectedVariant?->compare_at_amount;
    $currency = $selectedVariant?->currency ?? ($currentStore->default_currency ?? 'EUR');

    $inventory = $selectedVariant?->inventoryItem;
    $availableQuantity = $inventory?->availableQuantity();

    $swatchColors = [
        'black' => '#18181b', 'white' => '#fafafa', 'gray' => '#9ca3af', 'grey' => '#9ca3af',
        'red' => '#dc2626', 'blue' => '#2563eb', 'navy' => '#1e3a5f', 'green' => '#16a34a',
        'yellow' => '#eab308', 'orange' => '#ea580c', 'purple' => '#9333ea', 'pink' => '#ec4899',
        'brown' => '#92400e', 'beige' => '#d6c7a1', 'silver' => '#c0c0c0', 'gold' => '#d4af37',
    ];
@endphp

<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
    <x-storefront.breadcrumbs :items="array_filter([
        ['label' => __('Home'), 'url' => route('home')],
        ($primaryCollection = $product->collections->first())
            ? ['label' => $primaryCollection->title, 'url' => route('storefront.collections.show', $primaryCollection->handle)]
            : null,
        ['label' => $product->title],
    ])" />

    <div class="mt-6 lg:grid lg:grid-cols-2 lg:items-start lg:gap-12">
        {{-- Image gallery --}}
        <section class="lg:sticky lg:top-24" aria-label="{{ __('Product images') }}">
            <div class="aspect-square overflow-hidden rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                @if ($activeMedia !== null)
                    <img
                        src="{{ Storage::disk('public')->url($activeMedia->storage_key) }}"
                        alt="{{ $activeMedia->alt_text ?? $product->title }}"
                        class="size-full object-cover"
                    />
                @else
                    <div class="flex size-full items-center justify-center text-zinc-300 dark:text-zinc-600">
                        <svg class="size-20" fill="none" viewBox="0 0 24 24" stroke-width="0.75" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007Z" />
                        </svg>
                    </div>
                @endif
            </div>

            @if ($galleryMedia->count() > 1)
                <div class="mt-3 flex gap-2 overflow-x-auto pb-1" role="group" aria-label="{{ __('Image thumbnails') }}">
                    @foreach ($galleryMedia as $index => $media)
                        <button
                            type="button"
                            wire:click="$set('activeImageIndex', {{ $index }})"
                            class="{{ $index === $activeImageIndex ? 'border-(--sf-primary,#1d4ed8)' : 'border-transparent hover:border-zinc-300 dark:hover:border-zinc-600' }} size-16 shrink-0 overflow-hidden rounded-lg border-2 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600"
                            aria-label="{{ __('View image :current of :total', ['current' => $index + 1, 'total' => $galleryMedia->count()]) }}"
                            @if ($index === $activeImageIndex) aria-current="true" @endif
                        >
                            <img
                                src="{{ Storage::disk('public')->url($media->storage_key) }}"
                                alt=""
                                loading="lazy"
                                class="size-full object-cover"
                            />
                        </button>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Product info --}}
        <section class="mt-8 lg:mt-0" aria-label="{{ __('Product information') }}">
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl dark:text-white">
                {{ $product->title }}
            </h1>

            @if ($settings['show_vendor'] ?? true)
                @if (filled($product->vendor))
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $product->vendor }}</p>
                @endif
            @endif

            {{-- Price (live region: announced on variant change) --}}
            <div class="mt-4 flex items-center gap-3" aria-live="polite">
                <x-storefront.price :amount="$priceAmount" :currency="$currency" :compare-at-amount="$compareAtAmount" class="text-2xl" />
                @if ($compareAtAmount !== null && $compareAtAmount > $priceAmount)
                    <x-storefront.badge :text="__('Sale')" variant="sale" />
                @endif
            </div>

            {{-- Variant selector --}}
            @if ($product->options->isNotEmpty())
                <div class="mt-6 space-y-5">
                    @foreach ($product->options as $option)
                        <fieldset>
                            <legend class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $option->name }}</legend>
                            @if (strtolower($option->name) === 'color' && $option->values->count() <= 6)
                                <div class="mt-2.5 flex flex-wrap gap-2.5">
                                    @foreach ($option->values as $value)
                                        <label class="relative cursor-pointer" title="{{ $value->value }}">
                                            <input
                                                type="radio"
                                                name="option-{{ $option->getKey() }}"
                                                value="{{ $value->value }}"
                                                wire:model.live="selectedOptions.{{ $option->name }}"
                                                class="peer sr-only"
                                            />
                                            <span
                                                class="block size-8 rounded-full border border-zinc-300 transition peer-checked:ring-2 peer-checked:ring-(--sf-primary,#1d4ed8) peer-checked:ring-offset-2 peer-focus-visible:ring-2 peer-focus-visible:ring-blue-600 peer-focus-visible:ring-offset-2 dark:border-zinc-600 dark:peer-checked:ring-offset-zinc-950"
                                                style="background-color: {{ $swatchColors[strtolower($value->value)] ?? '#d4d4d8' }};"
                                                aria-hidden="true"
                                            ></span>
                                            <span class="sr-only">{{ $value->value }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @elseif ($option->values->count() <= 6)
                                <div class="mt-2.5 flex flex-wrap gap-2">
                                    @foreach ($option->values as $value)
                                        <label class="cursor-pointer">
                                            <input
                                                type="radio"
                                                name="option-{{ $option->getKey() }}"
                                                value="{{ $value->value }}"
                                                wire:model.live="selectedOptions.{{ $option->name }}"
                                                class="peer sr-only"
                                            />
                                            <span class="inline-flex min-w-11 items-center justify-center rounded-lg border border-zinc-300 px-3.5 py-2 text-sm font-medium text-zinc-700 transition hover:border-zinc-400 peer-checked:border-(--sf-primary,#1d4ed8) peer-checked:bg-(--sf-primary,#1d4ed8)/5 peer-checked:text-zinc-900 peer-focus-visible:ring-2 peer-focus-visible:ring-blue-600 dark:border-zinc-700 dark:text-zinc-300 dark:hover:border-zinc-500 dark:peer-checked:text-white">
                                                {{ $value->value }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            @else
                                <select
                                    wire:model.live="selectedOptions.{{ $option->name }}"
                                    class="mt-2.5 block w-full rounded-lg border border-zinc-300 bg-white py-2.5 pr-8 pl-3 text-sm text-zinc-900 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/30 sm:max-w-xs dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                                    aria-label="{{ $option->name }}"
                                >
                                    @foreach ($option->values as $value)
                                        <option value="{{ $value->value }}">{{ $value->value }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </fieldset>
                    @endforeach
                </div>
            @endif

            {{-- Stock messaging (live region) --}}
            <div class="mt-5 text-sm" aria-live="polite">
                @if ($selectedVariant === null)
                    <p class="flex items-center gap-1.5 text-zinc-500 dark:text-zinc-400">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" /></svg>
                        {{ __('This combination is unavailable') }}
                    </p>
                @elseif ($inventory === null || ($availableQuantity > 10))
                    <p class="flex items-center gap-1.5 font-medium text-green-700 dark:text-green-400">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                        {{ __('In stock') }}
                    </p>
                @elseif ($availableQuantity > 0)
                    <p class="flex items-center gap-1.5 font-medium text-amber-600 dark:text-amber-400">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                        {{ __('Only :count left in stock', ['count' => $availableQuantity]) }}
                    </p>
                @elseif ($inventory->policy === InventoryPolicy::Continue)
                    <p class="flex items-center gap-1.5 font-medium text-blue-700 dark:text-blue-400">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" /></svg>
                        {{ __('Available on backorder') }}
                    </p>
                @else
                    <p class="flex items-center gap-1.5 font-medium text-red-600 dark:text-red-400">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                        {{ __('Out of stock') }}
                    </p>
                @endif
            </div>

            {{-- Quantity + add to cart --}}
            <div class="mt-6 space-y-4">
                @if ($settings['show_quantity_selector'] ?? true)
                    <div>
                        <span class="mb-1.5 block text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Quantity') }}</span>
                        <x-storefront.quantity-selector
                            wire-model="quantity"
                            :min="1"
                            :max="$inventory !== null && $inventory->policy === InventoryPolicy::Deny ? max($availableQuantity, 1) : null"
                        />
                    </div>
                @endif

                @if ($isPurchasable)
                    <button
                        type="button"
                        wire:click="addToCart"
                        wire:loading.attr="disabled"
                        wire:target="addToCart"
                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-(--sf-primary,#1d4ed8) px-6 py-3.5 text-base font-semibold text-white transition hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 focus-visible:ring-offset-2 disabled:cursor-wait disabled:opacity-70 dark:focus-visible:ring-offset-zinc-950"
                    >
                        <span wire:loading.remove wire:target="addToCart">{{ __('Add to cart') }}</span>
                        <span wire:loading.flex wire:target="addToCart" class="items-center gap-2">
                            <svg class="size-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path>
                            </svg>
                            {{ __('Adding...') }}
                        </span>
                    </button>
                @else
                    <button
                        type="button"
                        disabled
                        class="w-full cursor-not-allowed rounded-xl bg-zinc-200 px-6 py-3.5 text-base font-semibold text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400"
                    >
                        {{ __('Sold out') }}
                    </button>
                @endif

                <div aria-live="polite">
                    @if ($addedToCart)
                        <p class="flex items-center gap-1.5 text-sm font-medium text-green-700 dark:text-green-400" role="status">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            {{ __('Added to cart') }}
                        </p>
                    @endif
                </div>
            </div>

            {{-- Description --}}
            @if (filled($product->description_html))
                <hr class="my-8 border-zinc-200 dark:border-zinc-800" />
                <div class="sf-prose">
                    {!! $product->description_html !!}
                </div>
            @endif

            {{-- Tags --}}
            @if (filled($product->tags))
                <div class="mt-8 flex flex-wrap gap-2">
                    @foreach ($product->tags as $tag)
                        <x-storefront.badge :text="$tag" />
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</div>
