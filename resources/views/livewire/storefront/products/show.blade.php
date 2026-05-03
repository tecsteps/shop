<div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 lg:grid-cols-2 lg:px-8">
    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-zinc-100 dark:border-zinc-800 dark:bg-zinc-900">
        @if($product->media->first())
            <img src="{{ asset('storage/'.$product->media->first()->storage_key) }}" alt="{{ $product->media->first()->alt_text ?: $product->title }}" class="aspect-square w-full object-cover">
        @else
            <div class="flex aspect-square items-center justify-center bg-[linear-gradient(135deg,#e4e4e7,#bae6fd,#d9f99d)] p-8 text-center text-3xl font-semibold text-zinc-800 dark:bg-[linear-gradient(135deg,#27272a,#0f766e,#1d4ed8)] dark:text-white">
                {{ $product->title }}
            </div>
        @endif
    </div>

    <div class="flex flex-col gap-6">
        <div>
            <a href="/collections" class="text-sm font-semibold text-zinc-600 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-white">Products</a>
            <h1 class="mt-3 text-3xl font-semibold tracking-normal">{{ $product->title }}</h1>
            @if($selectedVariant)
                <div class="mt-4 text-xl font-semibold">
                    @include('storefront.components.price', ['amount' => $selectedVariant->price_amount, 'currency' => $selectedVariant->currency])
                </div>
            @endif
        </div>

        @if($product->variants->count() > 1)
            <div>
                <div class="text-sm font-semibold">Options</div>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach($product->variants as $variant)
                        <button wire:key="variant-{{ $variant->id }}" wire:click="selectVariant({{ $variant->id }})" type="button" class="rounded-md border px-3 py-2 text-sm {{ $selectedVariant?->id === $variant->id ? 'border-zinc-950 bg-zinc-950 text-white dark:border-white dark:bg-white dark:text-zinc-950' : 'border-zinc-300 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-900' }}">
                            {{ $variant->optionValues->pluck('value')->join(' / ') ?: 'Default' }}
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="prose prose-zinc max-w-none dark:prose-invert">
            {!! $product->description_html !!}
        </div>

        <form wire:submit="addToCart" class="flex flex-col gap-3 sm:max-w-sm">
            <label class="text-sm font-semibold" for="quantity">Quantity</label>
            <input id="quantity" wire:model="quantity" type="number" min="1" max="9999" class="w-28 rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-950 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
            @error('quantity')
                <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
            @if(session('cart_status'))
                <p class="text-sm font-medium text-emerald-700 dark:text-emerald-400">{{ session('cart_status') }}</p>
            @endif
            <button type="submit" class="rounded-md bg-zinc-950 px-5 py-3 text-sm font-semibold text-white disabled:opacity-60 dark:bg-white dark:text-zinc-950" wire:loading.attr="disabled">
                Add to cart
            </button>
        </form>
    </div>
</div>
