<div>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-12 lg:px-8">
        {{-- Breadcrumbs --}}
        @php
            $primaryCollection = $product->collections->first();
            $breadcrumbs = [['label' => 'Home', 'url' => '/']];
            if ($primaryCollection) {
                $breadcrumbs[] = ['label' => $primaryCollection->title, 'url' => '/collections/' . $primaryCollection->handle];
            }
            $breadcrumbs[] = ['label' => $product->title];
        @endphp
        <x-storefront.breadcrumbs :items="$breadcrumbs" />

        <div class="mt-6 lg:flex lg:gap-12">
            {{-- Image Gallery --}}
            <div class="lg:w-1/2 lg:sticky lg:top-24 lg:self-start">
                @if($product->media->isNotEmpty())
                    {{-- Main image --}}
                    <div class="aspect-square overflow-hidden rounded-xl bg-zinc-100 dark:bg-zinc-800">
                        <img src="{{ $product->media[$selectedImageIndex]?->storage_key ?? '' }}"
                             alt="{{ $product->media[$selectedImageIndex]?->alt_text ?? $product->title }}"
                             class="h-full w-full object-cover"
                             loading="lazy">
                    </div>

                    {{-- Thumbnails --}}
                    @if($product->media->count() > 1)
                        <div class="mt-4 flex gap-2 overflow-x-auto" role="group" aria-label="Product images">
                            @foreach($product->media as $index => $media)
                                <button wire:click="$set('selectedImageIndex', {{ $index }})"
                                        class="h-16 w-16 shrink-0 overflow-hidden rounded-lg {{ $selectedImageIndex === $index ? 'ring-2 ring-blue-500' : 'ring-1 ring-zinc-200 dark:ring-zinc-700' }}"
                                        aria-label="View image {{ $index + 1 }} of {{ $product->media->count() }}">
                                    <img src="{{ $media->storage_key }}" alt="" class="h-full w-full object-cover" loading="lazy">
                                </button>
                            @endforeach
                        </div>
                    @endif
                @else
                    <div class="flex aspect-square items-center justify-center rounded-xl bg-zinc-100 dark:bg-zinc-800">
                        <svg class="h-16 w-16 text-zinc-300 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                        </svg>
                    </div>
                @endif
            </div>

            {{-- Product Info --}}
            <div class="mt-8 lg:mt-0 lg:w-1/2">
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white sm:text-3xl">{{ $product->title }}</h1>

                {{-- Price --}}
                <div class="mt-4" aria-live="polite">
                    @if($this->selectedVariant)
                        <x-storefront.price :amount="$this->selectedVariant->price_amount"
                                            :compare-at="$this->selectedVariant->compare_at_amount"
                                            :currency="$this->selectedVariant->currency"
                                            size="lg" />
                    @endif
                </div>

                {{-- Variant selectors --}}
                @if($product->options->isNotEmpty())
                    <div class="mt-6 space-y-5">
                        @foreach($product->options as $option)
                            <fieldset>
                                <legend class="text-sm font-medium text-zinc-900 dark:text-white">{{ $option->name }}</legend>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach($option->values as $value)
                                        <label class="cursor-pointer">
                                            <input type="radio"
                                                   wire:model.live="selectedOptions.{{ $option->name }}"
                                                   value="{{ $value->value }}"
                                                   class="peer sr-only">
                                            <span class="inline-flex items-center rounded-full border px-4 py-2 text-sm font-medium transition peer-checked:border-blue-600 peer-checked:bg-blue-50 peer-checked:text-blue-700 peer-focus:ring-2 peer-focus:ring-blue-500 peer-focus:ring-offset-2 border-zinc-300 text-zinc-700 hover:border-zinc-400 dark:border-zinc-600 dark:text-zinc-300 dark:peer-checked:border-blue-500 dark:peer-checked:bg-blue-950 dark:peer-checked:text-blue-300">
                                                {{ $value->value }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                        @endforeach
                    </div>
                @endif

                {{-- Stock messaging --}}
                <div class="mt-4" aria-live="polite">
                    @php $stockInfo = $this->stockInfo; @endphp
                    <span class="inline-flex items-center gap-1.5 text-sm
                        {{ match($stockInfo['status']) {
                            'in_stock' => 'text-green-600 dark:text-green-400',
                            'low_stock' => 'text-amber-600 dark:text-amber-400',
                            'sold_out' => 'text-red-600 dark:text-red-400',
                            'backorder' => 'text-blue-600 dark:text-blue-400',
                            default => 'text-zinc-500',
                        } }}">
                        @if($stockInfo['status'] === 'in_stock')
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                        @elseif($stockInfo['status'] === 'low_stock')
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                        @elseif($stockInfo['status'] === 'sold_out')
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                        @else
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" /></svg>
                        @endif
                        {{ $stockInfo['message'] }}
                    </span>
                </div>

                {{-- Quantity selector --}}
                <div class="mt-6">
                    <label for="quantity" class="text-sm font-medium text-zinc-900 dark:text-white">Quantity</label>
                    <div class="mt-2 inline-flex items-center rounded-lg border border-zinc-300 dark:border-zinc-600">
                        <button wire:click="$set('quantity', Math.max(1, {{ $quantity }} - 1))"
                                class="flex h-10 w-10 items-center justify-center text-zinc-500 hover:text-zinc-900 disabled:cursor-not-allowed disabled:opacity-50 dark:text-zinc-400 dark:hover:text-white"
                                {{ $quantity <= 1 ? 'disabled' : '' }}
                                aria-label="Decrease quantity">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" /></svg>
                        </button>
                        <input type="number" id="quantity" wire:model.live="quantity" min="1"
                               class="h-10 w-14 border-x border-zinc-300 text-center text-sm text-zinc-900 focus:ring-0 dark:border-zinc-600 dark:bg-transparent dark:text-white [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                               aria-label="Quantity">
                        <button wire:click="$set('quantity', {{ $quantity }} + 1)"
                                class="flex h-10 w-10 items-center justify-center text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white"
                                aria-label="Increase quantity">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        </button>
                    </div>
                </div>

                {{-- Add to cart --}}
                <div class="mt-6">
                    @if($stockInfo['canAddToCart'])
                        <button class="w-full rounded-lg bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Add to cart
                        </button>
                    @else
                        <button disabled class="w-full cursor-not-allowed rounded-lg bg-zinc-300 px-6 py-3 text-sm font-semibold text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400">
                            Sold out
                        </button>
                    @endif
                </div>

                {{-- Description --}}
                @if($product->description_html)
                    <div class="mt-8 border-t border-zinc-200 pt-8 dark:border-zinc-700">
                        <div class="prose prose-sm max-w-none text-zinc-600 dark:prose-invert dark:text-zinc-400">
                            {!! $product->description_html !!}
                        </div>
                    </div>
                @endif

                {{-- Tags --}}
                @if(!empty($product->tags))
                    <div class="mt-6 flex flex-wrap gap-2">
                        @foreach($product->tags as $tag)
                            <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">{{ $tag }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
