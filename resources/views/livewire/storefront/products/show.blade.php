@php
    $product = $this->product;
    $galleryItems = $this->gallery->map(fn ($media) => [
        'src' => Storage::url($media->storage_key),
        'alt' => $media->alt_text ?: $product->title,
    ])->values()->all();

    $primaryCollection = $product->collections()->where('status', 'active')->first();
    $stock = $this->stockMessage;
    $soldOut = $this->isSoldOut;
    $maxQty = $this->maxQuantity;

    $stockTones = [
        'success' => 'text-emerald-600 dark:text-emerald-400',
        'warning' => 'text-amber-600 dark:text-amber-400',
        'danger' => 'text-red-600 dark:text-red-400',
        'info' => 'text-blue-600 dark:text-blue-400',
        'muted' => 'text-zinc-500 dark:text-zinc-400',
    ];

    $colorMap = [
        'black' => '#171717', 'white' => '#f5f5f5', 'gray' => '#737373', 'grey' => '#737373',
        'red' => '#dc2626', 'blue' => '#2563eb', 'navy' => '#1e3a8a', 'green' => '#16a34a',
        'olive' => '#708238', 'yellow' => '#eab308', 'gold' => '#d4af37', 'orange' => '#ea580c',
        'pink' => '#ec4899', 'purple' => '#9333ea', 'brown' => '#92400e', 'beige' => '#d6c3a1',
        'cream' => '#f5ead6', 'denim' => '#4a6fa5', 'khaki' => '#bdb76b', 'silver' => '#c0c0c0',
        'camel' => '#b08968', 'multicolor' => 'linear-gradient(45deg, #ef4444, #f59e0b, #22c55e, #3b82f6, #a855f7)',
    ];

    $inputBase = 'w-full rounded-lg border border-zinc-300 bg-white px-3.5 py-2.5 text-sm text-zinc-900 transition focus:border-zinc-900 focus:outline-none focus:ring-2 focus:ring-zinc-900/20 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white dark:focus:border-white dark:focus:ring-white/20';
@endphp

<div>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <x-storefront-breadcrumbs :items="array_values(array_filter([
            ['label' => 'Home', 'url' => route('storefront.home')],
            $primaryCollection ? ['label' => $primaryCollection->title, 'url' => route('storefront.collection', ['handle' => $primaryCollection->handle])] : null,
            ['label' => $product->title],
        ]))" />

        <div class="mt-8 lg:grid lg:grid-cols-2 lg:gap-12">
            {{-- Image gallery --}}
            <div class="lg:sticky lg:top-24 lg:self-start" aria-label="Product images" role="region">
                @if ($galleryItems !== [])
                    <div
                        x-data="{
                            active: 0,
                            images: @js($galleryItems),
                            onScroll() {
                                const el = this.\$refs.scroller;
                                if (! el) return;
                                const index = Math.round(el.scrollLeft / el.clientWidth);
                                this.active = Math.min(Math.max(index, 0), this.images.length - 1);
                            },
                            scrollTo(i) {
                                const el = this.\$refs.scroller;
                                if (el) el.scrollTo({ left: i * el.clientWidth, behavior: 'smooth' });
                                this.active = i;
                            },
                        }"
                    >
                        {{-- Mobile: horizontal snap scroll --}}
                        <div
                            x-ref="scroller"
                            @scroll="onScroll()"
                            class="flex snap-x snap-mandatory overflow-x-auto lg:hidden"
                        >
                            <template x-for="(img, i) in images" :key="i">
                                <div class="w-full shrink-0 snap-center">
                                    <img :src="img.src" :alt="img.alt" class="aspect-square w-full object-cover" loading="lazy" />
                                </div>
                            </template>
                        </div>

                        {{-- Mobile: dots --}}
                        <div class="mt-3 flex justify-center gap-1.5 lg:hidden" aria-label="Choose image">
                            <template x-for="(img, i) in images" :key="i">
                                <button
                                    type="button"
                                    :aria-label="'View image ' + (i + 1) + ' of ' + images.length"
                                    :aria-current="active === i ? 'true' : 'false'"
                                    @click="scrollTo(i)"
                                    class="h-2 rounded-full transition"
                                    :class="active === i ? 'w-5 bg-zinc-900 dark:bg-white' : 'w-2 bg-zinc-300 dark:bg-zinc-600'"
                                ></button>
                            </template>
                        </div>

                        {{-- Desktop: main image + thumbnails --}}
                        <div class="hidden lg:block">
                            <div class="relative aspect-square overflow-hidden rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                                <template x-for="(img, i) in images" :key="i">
                                    <img
                                        :src="img.src"
                                        :alt="img.alt"
                                        x-show="active === i"
                                        x-cloak
                                        class="absolute inset-0 h-full w-full object-cover transition-opacity"
                                    />
                                </template>
                            </div>
                            @if (count($galleryItems) > 1)
                                <div class="mt-4 flex gap-2 overflow-x-auto pb-1">
                                    <template x-for="(img, i) in images" :key="i">
                                        <button
                                            type="button"
                                            @click="active = i"
                                            :aria-label="'View image ' + (i + 1) + ' of ' + images.length"
                                            :aria-current="active === i ? 'true' : 'false'"
                                            class="h-16 w-16 shrink-0 overflow-hidden rounded-lg border-2 transition"
                                            :class="active === i ? 'border-zinc-900 dark:border-white' : 'border-transparent hover:border-zinc-300 dark:hover:border-zinc-600'"
                                        >
                                            <img :src="img.src" alt="" class="h-full w-full object-cover" />
                                        </button>
                                    </template>
                                </div>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="flex aspect-square items-center justify-center rounded-2xl bg-zinc-100 text-zinc-300 dark:bg-zinc-800 dark:text-zinc-600">
                        <svg class="size-20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" />
                            <path d="M3 6h18" />
                            <path d="M16 10a4 4 0 0 1-8 0" />
                        </svg>
                    </div>
                @endif
            </div>

            {{-- Product info --}}
            <div class="mt-8 lg:mt-0">
                @if ($product->vendor)
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ $product->vendor }}</p>
                @endif

                <h1 class="mt-1 text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl dark:text-white">
                    {{ $product->title }}
                </h1>

                <div class="mt-4" aria-live="polite">
                    <x-storefront-price :amount="$this->price" :currency="$this->currency" :compare-at-amount="$this->compareAt" />
                </div>

                {{-- Variant selector --}}
                <div class="mt-8 space-y-6">
                    @foreach ($product->options as $option)
                        <fieldset>
                            <legend class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $option->name }}</legend>

                            @if (strtolower($option->name) === 'color' && $option->values->count() <= 12)
                                <div class="mt-3 flex flex-wrap items-center gap-3">
                                    @foreach ($option->values as $value)
                                        @php
                                            $selected = ($this->selectedOptions[$option->id] ?? null) === $value->id;
                                            $available = $this->optionAvailability[$value->id] ?? false;
                                            $color = $colorMap[strtolower($value->value)] ?? '#e5e5e5';
                                        @endphp
                                        <button
                                            type="button"
                                            wire:click="selectOption({{ $option->id }}, {{ $value->id }})"
                                            :disabled="{{ $available ? 'false' : 'true' }}"
                                            role="radio"
                                            aria-checked="{{ $selected ? 'true' : 'false' }}"
                                            aria-label="{{ $value->value }}"
                                            title="{{ $value->value }}"
                                            class="relative size-8 rounded-full border-2 transition focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2 dark:focus:ring-white {{ $selected ? 'ring-2 ring-zinc-900 ring-offset-2 dark:ring-white' : '' }} {{ $available ? 'cursor-pointer' : 'cursor-not-allowed opacity-40' }}"
                                            style="background-color: {{ $color }}"
                                        >
                                            @if ($available)
                                                <span class="sr-only">{{ $value->value }}</span>
                                            @else
                                                <span class="absolute inset-0 flex items-center justify-center" aria-hidden="true">
                                                    <svg class="size-4 text-zinc-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12" /></svg>
                                                </span>
                                            @endif
                                        </button>
                                    @endforeach
                                </div>
                            @elseif ($option->values->count() <= 6)
                                <div class="mt-3 flex flex-wrap gap-2" role="radiogroup" aria-label="{{ $option->name }}">
                                    @foreach ($option->values as $value)
                                        @php
                                            $selected = ($this->selectedOptions[$option->id] ?? null) === $value->id;
                                            $available = $this->optionAvailability[$value->id] ?? false;
                                        @endphp
                                        <button
                                            type="button"
                                            wire:click="selectOption({{ $option->id }}, {{ $value->id }})"
                                            :disabled="{{ $available ? 'false' : 'true' }}"
                                            role="radio"
                                            aria-checked="{{ $selected ? 'true' : 'false' }}"
                                            class="rounded-lg border px-4 py-2.5 text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2 dark:focus:ring-white {{ $selected ? 'border-zinc-900 bg-zinc-900 text-white dark:border-white dark:bg-white dark:text-zinc-900' : ($available ? 'border-zinc-300 text-zinc-800 hover:border-zinc-900 dark:border-zinc-700 dark:text-zinc-100 dark:hover:border-white' : 'cursor-not-allowed border-zinc-200 text-zinc-400 line-through opacity-60 dark:border-zinc-800 dark:text-zinc-600') }}"
                                        >
                                            {{ $value->value }}
                                        </button>
                                    @endforeach
                                </div>
                            @else
                                <div class="mt-3">
                                    <label for="option-{{ $option->id }}" class="sr-only">{{ $option->name }}</label>
                                    <select
                                        id="option-{{ $option->id }}"
                                        wire:model.live="selectedOptions.{{ $option->id }}"
                                        class="{{ $inputBase }}"
                                    >
                                        <option value="">Select {{ $option->name }}</option>
                                        @foreach ($option->values as $value)
                                            <option value="{{ $value->id }}" @disabled(! ($this->optionAvailability[$value->id] ?? false))>
                                                {{ $value->value }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        </fieldset>
                    @endforeach
                </div>

                {{-- Stock messaging --}}
                <p class="mt-5 flex items-center gap-1.5 text-sm font-medium {{ $stockTones[$stock['tone']] ?? $stockTones['muted'] }}" aria-live="polite">
                    @if ($stock['tone'] === 'success')
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" /><path d="m9 11 3 3L22 4" /></svg>
                    @elseif ($stock['tone'] === 'warning')
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z" /><path d="M12 9v4" /><path d="M12 17h.01" /></svg>
                    @elseif ($stock['tone'] === 'danger')
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10" /><path d="m15 9-6 6M9 9l6 6" /></svg>
                    @elseif ($stock['tone'] === 'info')
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10" /><path d="M12 16v-4" /><path d="M12 8h.01" /></svg>
                    @else
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10" /><path d="M12 16v-4" /><path d="M12 8h.01" /></svg>
                    @endif
                    {{ $stock['text'] }}
                </p>

                {{-- Quantity + add to cart --}}
                <div class="mt-6 flex flex-wrap items-end gap-4">
                    @if ($this->settings['show_quantity_selector'] ?? true)
                        <div>
                            <label for="quantity" class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Quantity</label>
                            <x-storefront-quantity-selector
                                :value="$this->quantity"
                                :min="1"
                                :max="$maxQty"
                                :disabled="$soldOut"
                                decrement="decrementQuantity"
                                increment="incrementQuantity"
                            />
                        </div>
                    @endif

                    <button
                        type="button"
                        wire:click="addToCart"
                        wire:loading.attr="disabled"
                        class="flex-1 basis-48 rounded-lg bg-blue-600 px-6 py-3.5 text-base font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:bg-blue-500 dark:hover:bg-blue-400 {{ $soldOut ? '!bg-zinc-200 !text-zinc-400 dark:!bg-zinc-800 dark:!text-zinc-500' : '' }}"
                    >
                        @if ($soldOut)
                            Sold out
                        @else
                            <span wire:loading.remove wire:target="addToCart">Add to cart</span>
                            <span wire:loading wire:target="addToCart" class="inline-flex items-center gap-2">
                                <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z" />
                                </svg>
                                Adding...
                            </span>
                        @endif
                    </button>
                </div>

                @if ($this->addToCartError)
                    <p class="mt-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 dark:bg-red-950/40 dark:text-red-300" role="alert">
                        {{ $this->addToCartError }}
                    </p>
                @endif

                {{-- Description --}}
                @if ($product->description_html)
                    <hr class="my-8 border-zinc-200 dark:border-zinc-800" />
                    <div class="space-y-4 text-zinc-700 [&_a]:font-medium [&_a]:text-blue-600 [&_a]:underline [&_a]:underline-offset-2 [&_h2]:text-xl [&_h2]:font-bold [&_h2]:text-zinc-900 [&_h3]:text-lg [&_h3]:font-semibold [&_h3]:text-zinc-900 [&_li]:mb-1 [&_ol]:list-decimal [&_ol]:pl-5 [&_p]:leading-relaxed [&_ul]:list-disc [&_ul]:pl-5 dark:text-zinc-300 dark:[&_h2]:text-white dark:[&_h3]:text-white">
                        {!! $product->description_html !!}
                    </div>
                @endif

                {{-- Tags --}}
                @if (is_array($product->tags) && $product->tags !== [])
                    <div class="mt-6 flex flex-wrap gap-2">
                        @foreach ($product->tags as $tag)
                            <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                                {{ $tag }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
