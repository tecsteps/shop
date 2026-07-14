@php
    $seoVariant = $this->selectedVariant ?: $product->variants->first();
    $seoMedia = $product->media->first();
    $seoImage = $seoMedia ? Storage::disk('public')->url($seoMedia->storage_key) : null;
    $seoDescription = str(strip_tags((string) $product->description_html))->squish()->limit(160)->toString();
    $productStructuredData = json_encode(array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product->title,
        'description' => $seoDescription,
        'image' => $seoImage,
        'sku' => $seoVariant?->sku,
        'offers' => $seoVariant ? [
            '@type' => 'Offer',
            'priceCurrency' => $seoVariant->currency,
            'price' => number_format($seoVariant->price_amount / 100, 2, '.', ''),
            'availability' => $this->canAddToCart ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'url' => url('/products/'.$product->handle),
        ] : null,
    ]), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
@endphp
@push('head')
    <meta property="og:title" content="{{ $product->title }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:type" content="product">
    <meta property="og:url" content="{{ url('/products/'.$product->handle) }}">
    @if($seoImage)<meta property="og:image" content="{{ $seoImage }}">@endif
    @if($seoVariant)<meta property="product:price:amount" content="{{ number_format($seoVariant->price_amount / 100, 2, '.', '') }}"><meta property="product:price:currency" content="{{ $seoVariant->currency }}">@endif
    <script type="application/ld+json">{!! $productStructuredData !!}</script>
@endpush
<div class="sf-container sf-page-y">
    <x-storefront.breadcrumbs :items="[
        ['label' => 'Home', 'url' => url('/')],
        $product->collections->first() ? ['label' => $product->collections->first()->title, 'url' => url('/collections/'.$product->collections->first()->handle)] : ['label' => 'Products', 'url' => url('/collections')],
        ['label' => $product->title],
    ]" />

    <div class="mt-7 grid gap-10 lg:grid-cols-2 lg:gap-14">
        <section class="min-w-0 lg:sticky lg:top-28 lg:self-start" aria-label="Product images" x-data="{ active: @entangle('activeMedia').live }">
            @if ($product->media->isEmpty())
                <div class="grid aspect-square place-items-center rounded-3xl bg-slate-100 text-slate-300 dark:bg-slate-800 dark:text-slate-600">
                    <svg aria-hidden="true" class="size-20" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round" d="M6 8h12l1 13H5L6 8Zm3 0V6a3 3 0 0 1 6 0v2"/></svg>
                </div>
            @else
                <div class="hidden sm:block">
                    @foreach ($product->media as $index => $media)
                        <div x-show="active === {{ $index }}" x-cloak @if ($index === 0) style="display:block" @endif class="aspect-square overflow-hidden rounded-3xl bg-slate-100 dark:bg-slate-800">
                            <img src="{{ Storage::disk('public')->url($media->storage_key) }}" alt="{{ $media->alt_text ?: $product->title.' image '.($index + 1) }}" class="h-full w-full object-cover">
                        </div>
                    @endforeach
                    <div class="mt-4 flex gap-3 overflow-x-auto pb-2">
                        @foreach ($product->media as $index => $media)
                            <button type="button" @click="active = {{ $index }}" :aria-current="active === {{ $index }} ? 'true' : 'false'" class="size-16 shrink-0 overflow-hidden rounded-xl border-2 border-transparent bg-slate-100 p-0.5 aria-[current=true]:border-blue-600 dark:bg-slate-800" aria-label="View image {{ $index + 1 }} of {{ $product->media->count() }}">
                                <img src="{{ Storage::disk('public')->url($media->storage_key) }}" alt="" class="h-full w-full rounded-lg object-cover">
                            </button>
                        @endforeach
                    </div>
                </div>
                <div class="flex snap-x snap-mandatory gap-3 overflow-x-auto sm:hidden">
                    @foreach ($product->media as $index => $media)
                        <img src="{{ Storage::disk('public')->url($media->storage_key) }}" alt="{{ $media->alt_text ?: $product->title.' image '.($index + 1) }}" class="aspect-square w-full shrink-0 snap-center rounded-2xl object-cover">
                    @endforeach
                </div>
            @endif
        </section>

        <section>
            @if ($product->vendor)<p class="sf-eyebrow">{{ $product->vendor }}</p>@endif
            <h1 class="sf-page-title mt-2">{{ $product->title }}</h1>

            <div class="mt-5 flex flex-wrap items-center gap-3 text-xl" aria-live="polite" aria-atomic="true">
                @if ($this->selectedVariant)
                    <x-storefront.price :amount="$this->selectedVariant->price_amount" :currency="$this->selectedVariant->currency" :compare-at-amount="$this->selectedVariant->compare_at_amount" />
                @else
                    <span class="text-slate-500">Choose options to see price</span>
                @endif
            </div>

            @error('variant')<div role="alert" class="sf-callout sf-callout-error mt-5">{{ $message }}</div>@enderror

            <div class="mt-8 space-y-7">
                @foreach ($product->options as $option)
                    <fieldset>
                        <legend class="mb-3 text-sm font-semibold">{{ $option->name }}: <span class="font-normal text-slate-500">{{ $selectedOptions[$option->name] ?? '' }}</span></legend>
                        @if ($option->values->count() <= 6)
                            <div class="flex flex-wrap gap-2" role="radiogroup" aria-label="{{ $option->name }}">
                                @foreach ($option->values as $value)
                                    <label class="relative cursor-pointer">
                                        <input type="radio" wire:model.live="selectedOptions.{{ $option->name }}" value="{{ $value->value }}" class="peer sr-only">
                                        <span class="grid min-h-11 min-w-11 place-items-center rounded-xl border border-slate-300 px-4 text-sm font-medium transition hover:border-slate-500 peer-checked:border-blue-600 peer-checked:bg-blue-50 peer-focus-visible:ring-2 peer-focus-visible:ring-blue-500 peer-focus-visible:ring-offset-2 dark:border-slate-700 dark:peer-checked:bg-blue-950/60">{{ $value->value }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @else
                            <select wire:model.live="selectedOptions.{{ $option->name }}" class="sf-select w-full" aria-label="{{ $option->name }}">@foreach ($option->values as $value)<option value="{{ $value->value }}">{{ $value->value }}</option>@endforeach</select>
                        @endif
                    </fieldset>
                @endforeach
            </div>

            @php($inventory = $this->selectedVariant?->inventoryItem)
            <div class="mt-7 min-h-6 text-sm font-medium" aria-live="polite">
                @if ($this->selectedVariant && $inventory)
                    @if ($this->availableQuantity === null && (int) $inventory->quantity_on_hand <= 0)
                        <span class="inline-flex items-center gap-2 text-blue-700 dark:text-blue-300"><span class="size-2 rounded-full bg-blue-500"></span>Available on backorder</span>
                    @elseif ($this->availableQuantity === 0)
                        <span class="inline-flex items-center gap-2 text-red-700 dark:text-red-300"><span class="size-2 rounded-full bg-red-500"></span>Out of stock</span>
                    @elseif ($this->availableQuantity !== null && $this->availableQuantity <= 10)
                        <span class="inline-flex items-center gap-2 text-amber-700 dark:text-amber-300"><span class="size-2 rounded-full bg-amber-500"></span>Only {{ $this->availableQuantity }} left in stock</span>
                    @else
                        <span class="inline-flex items-center gap-2 text-emerald-700 dark:text-emerald-300"><span class="size-2 rounded-full bg-emerald-500"></span>In stock</span>
                    @endif
                @endif
            </div>

            <div class="mt-5 flex items-stretch gap-3">
                <x-storefront.quantity-selector wire-model="quantity" :value="$quantity" :max="$this->availableQuantity" />
                <button type="button" wire:click="addToCart" wire:loading.attr="disabled" wire:target="addToCart" @disabled(! $this->canAddToCart) class="sf-button sf-button-primary min-h-12 flex-1 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:text-slate-600 dark:disabled:bg-slate-800 dark:disabled:text-slate-400">
                    <span wire:loading.remove wire:target="addToCart">{{ $this->canAddToCart ? 'Add to cart' : 'Sold out' }}</span>
                    <span wire:loading wire:target="addToCart">Adding...</span>
                </button>
            </div>

            @if ($product->description_html)
                <div class="mt-10 border-t border-slate-200 pt-8 dark:border-slate-800"><x-storefront.rich-text :html="$product->description_html" /></div>
            @endif

            @php($tags = is_string($product->tags) ? json_decode($product->tags, true) : $product->tags)
            @if (is_array($tags) && $tags !== [])
                <div class="mt-8 flex flex-wrap gap-2" aria-label="Product tags">@foreach ($tags as $tag)<span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $tag }}</span>@endforeach</div>
            @endif
        </section>
    </div>
</div>
