@php
    $selectedVariant = collect($variants)->firstWhere('id', $selectedVariantId);
    $currency = $selectedVariant->currency ?? (app()->bound('current_store') ? app('current_store')->default_currency : 'USD');
    $tags = is_string($product->tags ?? null) ? (json_decode($product->tags, true) ?: []) : ($product->tags ?? []);
@endphp
<div class="mx-auto max-w-6xl px-6 py-12">
    <x-storefront.breadcrumbs class="mb-4" :items="[
        ['label' => 'Home', 'url' => route('storefront.home')],
        ['label' => 'Products'],
        ['label' => $product->title],
    ]" />

    <div class="grid gap-8 lg:grid-cols-2">
        <div class="space-y-3">
            @php
                $resolveMediaUrl = fn ($item) => str_starts_with($item->storage_key, 'http') ? $item->storage_key : '/storage/'.$item->storage_key;
            @endphp
            @if (! empty($media))
                <div class="aspect-square w-full overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800">
                    <img src="{{ $resolveMediaUrl($media[0]) }}" alt="{{ $media[0]->alt_text ?? $product->title }}" class="h-full w-full object-cover" />
                </div>
                @if (count($media) > 1)
                    <div class="grid grid-cols-4 gap-2">
                        @foreach (array_slice($media, 1, 7) as $item)
                            <div wire:key="pm-{{ $item->id }}" class="aspect-square overflow-hidden rounded-md bg-zinc-100 dark:bg-zinc-800">
                                <img src="{{ $resolveMediaUrl($item) }}" alt="{{ $item->alt_text ?? '' }}" class="h-full w-full object-cover" loading="lazy" />
                            </div>
                        @endforeach
                    </div>
                @endif
            @else
                <div class="flex aspect-square w-full items-center justify-center rounded-lg bg-zinc-100 text-zinc-400 dark:bg-zinc-800">
                    <flux:icon name="photo" class="size-12" />
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <flux:heading size="xl">{{ $product->title }}</flux:heading>

            @if ($selectedVariant)
                <x-storefront.price
                    :amount="$selectedVariant->price_amount"
                    :compare-at="$selectedVariant->compare_at_amount"
                    :currency="$currency"
                    class="text-xl" />
            @endif

            @if (count($variants) > 1)
                <div>
                    <label for="variant" class="mb-1 block text-sm font-medium">Variant</label>
                    <flux:select id="variant" wire:model.live="selectedVariantId">
                        @foreach ($variants as $variant)
                            <flux:select.option value="{{ $variant->id }}" wire:key="v-{{ $variant->id }}">
                                {{ $variant->sku ?: 'Variant #'.$variant->id }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            @endif

            <div class="flex items-center gap-3">
                <label for="quantity" class="text-sm font-medium">Quantity</label>
                <input id="quantity" type="number" min="1" max="99"
                       wire:model.live="quantity"
                       class="h-9 w-20 rounded-md border border-zinc-200 text-center text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100" />
            </div>

            <flux:button
                variant="primary"
                wire:click="addToCart"
                data-testid="add-to-cart"
                :disabled="$selectedVariantId === null">
                Add to cart
            </flux:button>

            @if (! empty($product->description_html))
                <div class="prose prose-zinc max-w-none dark:prose-invert">{!! $product->description_html !!}</div>
            @endif

            @if (! empty($tags))
                <div class="flex flex-wrap gap-2">
                    @foreach ($tags as $tag)
                        <x-storefront.badge>{{ $tag }}</x-storefront.badge>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
