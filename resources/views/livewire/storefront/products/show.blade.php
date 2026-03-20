<div>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        {{-- Breadcrumbs --}}
        @include('storefront.components.breadcrumbs', ['items' => [
            ['label' => 'Home', 'url' => '/'],
            ['label' => $product->title],
        ]])

        <div class="mt-6 lg:grid lg:grid-cols-2 lg:gap-12">
            {{-- Image Gallery --}}
            <div class="lg:sticky lg:top-24">
                @if($product->media->isNotEmpty())
                    <div class="aspect-square overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800">
                        <img src="{{ $product->media->first()->url ?? '' }}"
                             alt="{{ $product->title }}"
                             class="h-full w-full object-cover">
                    </div>
                    @if($product->media->count() > 1)
                        <div class="mt-4 flex gap-2 overflow-x-auto lg:grid lg:grid-cols-4 lg:gap-2">
                            @foreach($product->media as $media)
                                <div class="aspect-square w-20 shrink-0 cursor-pointer overflow-hidden rounded-md border-2 border-transparent bg-zinc-100 hover:border-zinc-400 lg:w-full dark:bg-zinc-800">
                                    <img src="{{ $media->url ?? '' }}" alt="{{ $product->title }}" class="h-full w-full object-cover">
                                </div>
                            @endforeach
                        </div>
                    @endif
                @else
                    <div class="flex aspect-square items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800">
                        <svg class="h-24 w-24 text-zinc-300 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
                        </svg>
                    </div>
                @endif
            </div>

            {{-- Product Info --}}
            <div class="mt-8 lg:mt-0">
                <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">{{ $product->title }}</h1>

                {{-- Price --}}
                @if($this->selectedVariant)
                    <div class="mt-4">
                        @include('storefront.components.price', [
                            'amount' => $this->selectedVariant->price_amount,
                            'currency' => $currentStore->default_currency ?? 'EUR',
                            'compareAtAmount' => $this->selectedVariant->compare_at_amount,
                        ])
                    </div>
                @endif

                {{-- Variant Selection --}}
                @if($product->variants->count() > 1)
                    <fieldset class="mt-6 space-y-4" aria-label="Product variants">
                        <legend class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Options</legend>
                        <div class="flex flex-wrap gap-2">
                            @foreach($product->variants as $variant)
                                <button wire:click="selectVariant({{ $variant->id }})"
                                        aria-label="{{ $variant->title }}"
                                        @class([
                                            'rounded-md border px-4 py-2 text-sm font-medium transition',
                                            'border-zinc-900 bg-zinc-900 text-white dark:border-white dark:bg-white dark:text-zinc-900' => $selectedVariantId === $variant->id,
                                            'border-zinc-300 text-zinc-700 hover:border-zinc-400 dark:border-zinc-600 dark:text-zinc-300 dark:hover:border-zinc-500' => $selectedVariantId !== $variant->id,
                                        ])>
                                    {{ $variant->title }}
                                </button>
                            @endforeach
                        </div>
                    </fieldset>
                @endif

                {{-- Stock Status --}}
                @if($this->isSoldOut)
                    <div class="mt-4 rounded-md bg-red-50 px-4 py-3 text-sm font-medium text-red-700 dark:bg-red-900/20 dark:text-red-400">
                        Sold out
                    </div>
                @elseif($this->isBackorder)
                    <div class="mt-4 rounded-md bg-amber-50 px-4 py-3 text-sm font-medium text-amber-700 dark:bg-amber-900/20 dark:text-amber-400">
                        Available on backorder
                    </div>
                @endif

                {{-- Quantity & Add to Cart --}}
                <div class="mt-6 flex items-center gap-4">
                    @unless($this->isSoldOut)
                        @include('storefront.components.quantity-selector', [
                            'value' => $quantity,
                            'min' => 1,
                            'max' => 99,
                            'wireModel' => 'quantity',
                        ])
                    @endunless

                    <button wire:click="addToCart"
                            @if($this->isSoldOut) disabled @endif
                            @class([
                                'flex-1 rounded-md px-6 py-3 text-sm font-semibold transition',
                                'bg-zinc-300 text-zinc-500 cursor-not-allowed dark:bg-zinc-700 dark:text-zinc-500' => $this->isSoldOut,
                                'bg-zinc-900 text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200' => !$this->isSoldOut,
                            ])>
                        @if($this->isSoldOut)
                            Sold out
                        @else
                            Add to cart
                        @endif
                    </button>
                </div>

                {{-- Description --}}
                @if($product->description_html)
                    <div class="prose dark:prose-invert mt-8 max-w-none">
                        {!! $product->description_html !!}
                    </div>
                @endif

                {{-- Tags --}}
                @if(!empty($product->tags))
                    <div class="mt-6 flex flex-wrap gap-2">
                        @foreach($product->tags as $tag)
                            <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300">
                                {{ $tag }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
