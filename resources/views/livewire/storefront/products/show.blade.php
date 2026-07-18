@php
    $variant = $this->selectedVariant;
    $available = $this->availableQuantity;
    $media = $product->media->sortBy('position');
    $mainImage = $media->first();
    $primaryCollection = $product->collections->first();
@endphp

<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <x-storefront.breadcrumbs :items="array_filter([
        ['label' => 'Home', 'url' => route('home')],
        $primaryCollection ? ['label' => $primaryCollection->title, 'url' => route('storefront.collections.show', $primaryCollection->handle)] : null,
        ['label' => $product->title, 'url' => null],
    ])" />

    <div class="mt-6 grid gap-10 lg:grid-cols-2">
        <div role="region" aria-label="Product images" class="lg:sticky lg:top-24 lg:self-start">
            <div class="aspect-square overflow-hidden rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                @if ($mainImage)
                    <img
                        src="{{ $mainImage->url ?? Storage::url($mainImage->storage_key) }}"
                        alt="{{ $mainImage->alt_text ?? $product->title }}"
                        class="size-full object-cover"
                    />
                @else
                    <div class="flex size-full items-center justify-center text-zinc-400 dark:text-zinc-600">
                        <flux:icon name="shopping-bag" class="size-16" />
                    </div>
                @endif
            </div>

            @if ($media->count() > 1)
                <div class="mt-3 flex gap-2 overflow-x-auto">
                    @foreach ($media as $index => $item)
                        <button type="button" class="size-16 shrink-0 overflow-hidden rounded-lg border-2 {{ $loop->first ? 'border-blue-600' : 'border-transparent' }}" aria-label="View image {{ $index + 1 }} of {{ $media->count() }}">
                            <img src="{{ $item->url ?? Storage::url($item->storage_key) }}" alt="" class="size-full object-cover" />
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <div>
            <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">{{ $product->title }}</h1>

            <div class="mt-3" aria-live="polite">
                @if ($variant)
                    <x-storefront.price :amount="$variant->price_amount" :currency="$variant->currency" :compare-at-amount="$variant->compare_at_amount" />
                @else
                    <span class="text-zinc-500 dark:text-zinc-400">Select options to see price</span>
                @endif
            </div>

            @foreach ($this->optionGroups as $group)
                <fieldset class="mt-6">
                    <legend class="text-sm font-medium text-zinc-900 dark:text-white">{{ $group['name'] }}</legend>

                    @if ($group['name'] === 'Color')
                        <div class="mt-2 flex gap-2">
                            @foreach ($group['values'] as $value)
                                <button
                                    type="button"
                                    wire:click="selectOption('{{ $group['name'] }}', '{{ $value }}')"
                                    title="{{ $value }}"
                                    aria-label="{{ $value }}"
                                    aria-pressed="{{ ($selectedOptions[$group['name']] ?? null) === $value ? 'true' : 'false' }}"
                                    style="background-color: {{ strtolower($value) }}"
                                    class="size-8 rounded-full border border-zinc-300 {{ ($selectedOptions[$group['name']] ?? null) === $value ? 'ring-2 ring-blue-600 ring-offset-2' : '' }}"
                                ></button>
                            @endforeach
                        </div>
                    @elseif (count($group['values']) > 6)
                        <select wire:change="selectOption('{{ $group['name'] }}', $event.target.value)" class="mt-2 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                            @foreach ($group['values'] as $value)
                                <option value="{{ $value }}" @selected(($selectedOptions[$group['name']] ?? null) === $value)>{{ $value }}</option>
                            @endforeach
                        </select>
                    @else
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($group['values'] as $value)
                                <button
                                    type="button"
                                    wire:click="selectOption('{{ $group['name'] }}', '{{ $value }}')"
                                    aria-pressed="{{ ($selectedOptions[$group['name']] ?? null) === $value ? 'true' : 'false' }}"
                                    class="rounded-lg border px-4 py-2 text-sm font-medium {{ ($selectedOptions[$group['name']] ?? null) === $value ? 'border-blue-600 bg-blue-50 text-blue-700 ring-1 ring-blue-600 dark:bg-blue-950 dark:text-blue-300' : 'border-zinc-300 text-zinc-700 hover:border-zinc-500 dark:border-zinc-700 dark:text-zinc-200' }}"
                                >
                                    {{ $value }}
                                </button>
                            @endforeach
                        </div>
                    @endif
                </fieldset>
            @endforeach

            <div class="mt-4 text-sm" aria-live="polite">
                @if (! $variant)
                    <span class="inline-flex items-center gap-1 text-zinc-500 dark:text-zinc-400">
                        <flux:icon name="information-circle" class="size-4" /> Select all options to check availability
                    </span>
                @elseif ($available === null)
                    <span class="inline-flex items-center gap-1 text-green-600 dark:text-green-400">
                        <flux:icon name="check-circle" class="size-4" /> In stock
                    </span>
                @elseif ($available <= 0)
                    @if ($variant->inventoryItem?->policy->value === 'continue')
                        <span class="inline-flex items-center gap-1 text-blue-600 dark:text-blue-400">
                            <flux:icon name="information-circle" class="size-4" /> Available on backorder
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 text-red-600 dark:text-red-400">
                            <flux:icon name="x-circle" class="size-4" /> Out of stock
                        </span>
                    @endif
                @elseif ($available <= 10)
                    <span class="inline-flex items-center gap-1 text-amber-600 dark:text-amber-400">
                        <flux:icon name="exclamation-triangle" class="size-4" /> Only {{ $available }} left in stock
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 text-green-600 dark:text-green-400">
                        <flux:icon name="check-circle" class="size-4" /> In stock
                    </span>
                @endif
            </div>

            @error('quantity')
                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
            @error('variant')
                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror

            <div class="mt-4">
                <x-storefront.quantity-selector :value="$quantity" wire-model="quantity" :max="$available" />
            </div>

            @php
                $soldOut = $variant && $available !== null && $available <= 0 && $variant->inventoryItem?->policy->value === 'deny';
            @endphp

            <flux:button
                wire:click="addToCart"
                wire:loading.attr="disabled"
                wire:target="addToCart"
                :disabled="$soldOut || ! $variant"
                variant="primary"
                class="mt-6 w-full"
            >
                <span wire:loading.remove wire:target="addToCart">{{ $soldOut ? 'Sold out' : 'Add to cart' }}</span>
                <span wire:loading wire:target="addToCart">Adding...</span>
            </flux:button>

            @if ($addedToCart)
                <p class="mt-2 text-sm text-green-600 dark:text-green-400" role="status">Added to cart</p>
            @endif

            @if ($product->description_html)
                <div class="prose prose-zinc dark:prose-invert mt-8 max-w-none border-t border-zinc-200 pt-8 dark:border-zinc-800">
                    {!! $product->description_html !!}
                </div>
            @endif

            @if (! empty($product->tags))
                <div class="mt-6 flex flex-wrap gap-2">
                    @foreach ($product->tags as $tag)
                        <x-storefront.badge :text="$tag" />
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
