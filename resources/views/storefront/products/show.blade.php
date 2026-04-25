<x-storefront.layout :title="$product->title">
    @php($variant = $product->variants->firstWhere('is_default', true) ?? $product->variants->first())
    @php($inventory = $variant?->inventoryItem)
    <section class="mx-auto grid max-w-7xl gap-10 px-4 py-10 lg:grid-cols-2">
        <div class="grid gap-4">
            <img src="{{ $product->media->first()?->url }}" alt="{{ $product->media->first()?->alt_text ?? $product->title }}" class="aspect-[4/5] w-full rounded-lg object-cover">
        </div>
        <div class="grid content-start gap-6">
            <nav class="text-sm text-zinc-600 dark:text-zinc-400"><a href="{{ route('collections.index') }}">Collections</a> / {{ $product->title }}</nav>
            <div>
                <h1 class="text-3xl font-bold tracking-normal">{{ $product->title }}</h1>
                <div class="mt-3 flex items-center gap-3 text-xl font-semibold">
                    <x-shop.price :amount="$variant->price_amount" :currency="$variant->currency" />
                    @if($variant->compare_at_amount)
                        <span class="text-base font-normal text-zinc-500 line-through"><x-shop.price :amount="$variant->compare_at_amount" :currency="$variant->currency" /></span>
                    @endif
                </div>
            </div>

            <div class="grid gap-3">
                @foreach($product->options as $option)
                    <fieldset>
                        <legend class="text-sm font-medium">{{ $option->name }}</legend>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach($option->values as $value)
                                <span class="rounded-md border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700">{{ $value->value }}</span>
                            @endforeach
                        </div>
                    </fieldset>
                @endforeach
            </div>

            <div class="rounded-md border border-zinc-200 p-4 text-sm dark:border-zinc-800">
                @if($inventory && $inventory->availableForSale() <= 0 && $inventory->policy === 'deny')
                    <strong>Out of stock.</strong> This product cannot be added to cart.
                @elseif($inventory && $inventory->availableForSale() <= 0 && $inventory->policy === 'continue')
                    <strong>Backorder available.</strong> This product ships when stock returns.
                @else
                    <strong>In stock.</strong> {{ $inventory?->availableForSale() ?? 0 }} available.
                @endif
            </div>

            <form method="POST" action="{{ route('cart.add') }}" class="grid gap-4">
                @csrf
                <input type="hidden" name="variant_id" value="{{ $variant->id }}">
                <label class="grid gap-2">
                    <span class="text-sm font-medium">Quantity</span>
                    <input name="quantity" type="number" min="1" value="1" class="w-28 rounded-md border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900">
                </label>
                <button @disabled($inventory && $inventory->availableForSale() <= 0 && $inventory->policy === 'deny') class="rounded-md bg-zinc-950 px-5 py-3 font-medium text-white disabled:cursor-not-allowed disabled:bg-zinc-400 dark:bg-white dark:text-zinc-950">Add to cart</button>
            </form>

            <div class="prose prose-zinc max-w-none dark:prose-invert">{!! $product->description_html !!}</div>
        </div>
    </section>
</x-storefront.layout>

