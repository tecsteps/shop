<x-storefront.layout :title="'Cart'">
    <section class="mx-auto grid max-w-7xl gap-8 px-4 py-10 lg:grid-cols-[1fr_360px]">
        <div>
            <h1 class="text-3xl font-bold tracking-normal">Cart</h1>
            <div class="mt-6 grid gap-4">
                @forelse($cart->lines as $line)
                    <div class="grid gap-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-800 sm:grid-cols-[96px_1fr_auto]">
                        <img src="{{ $line->snapshot_json['image'] ?? 'https://placehold.co/200x240' }}" alt="{{ $line->snapshot_json['product_title'] ?? 'Cart item' }}" class="aspect-[4/5] w-24 rounded-md object-cover">
                        <div>
                            <h2 class="font-semibold">{{ $line->snapshot_json['product_title'] ?? $line->variant->product->title }}</h2>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $line->snapshot_json['variant_title'] ?? '' }}</p>
                            <p class="mt-2"><x-shop.price :amount="$line->unit_price_amount" :currency="$cart->currency" /></p>
                        </div>
                        <form method="POST" action="{{ route('cart.update', $line) }}" class="flex items-end gap-2">
                            @csrf
                            @method('PATCH')
                            <label class="grid gap-1">
                                <span class="text-sm">Quantity</span>
                                <input name="quantity" type="number" min="0" value="{{ $line->quantity }}" class="w-24 rounded-md border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900">
                            </label>
                            <button class="rounded-md border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700">Update</button>
                        </form>
                    </div>
                @empty
                    <div class="rounded-lg border border-zinc-200 p-8 text-center dark:border-zinc-800">
                        <p>Your cart is empty.</p>
                        <a class="mt-4 inline-flex rounded-md bg-zinc-950 px-4 py-2 text-white dark:bg-white dark:text-zinc-950" href="{{ route('collections.index') }}">Continue shopping</a>
                    </div>
                @endforelse
            </div>
        </div>
        <aside class="h-max rounded-lg border border-zinc-200 p-5 dark:border-zinc-800">
            <h2 class="font-semibold">Order summary</h2>
            <dl class="mt-4 grid gap-2 text-sm">
                <div class="flex justify-between"><dt>Subtotal</dt><dd><x-shop.price :amount="$totals['subtotal']" :currency="$totals['currency']" /></dd></div>
                <div class="flex justify-between"><dt>Discount</dt><dd>-<x-shop.price :amount="$totals['discount']" :currency="$totals['currency']" /></dd></div>
                <div class="flex justify-between border-t border-zinc-200 pt-2 text-base font-semibold dark:border-zinc-800"><dt>Total</dt><dd><x-shop.price :amount="$totals['total']" :currency="$totals['currency']" /></dd></div>
            </dl>
            <form method="POST" action="{{ route('cart.discount') }}" class="mt-5 grid gap-2">
                @csrf
                <label class="text-sm font-medium" for="discount_code">Discount code</label>
                <div class="flex gap-2">
                    <input id="discount_code" name="discount_code" value="{{ $cart->discount_code }}" class="min-w-0 flex-1 rounded-md border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900">
                    <button class="rounded-md border border-zinc-300 px-3 py-2 dark:border-zinc-700">Apply</button>
                </div>
            </form>
            @if($cart->discount_code)
                <form method="POST" action="{{ route('cart.discount.remove') }}" class="mt-2">
                    @csrf
                    @method('DELETE')
                    <button class="text-sm text-zinc-600 underline dark:text-zinc-400">Remove {{ $cart->discount_code }}</button>
                </form>
            @endif
            <form method="POST" action="{{ route('cart.checkout') }}" class="mt-5">
                @csrf
                <button class="w-full rounded-md bg-zinc-950 px-4 py-3 font-medium text-white dark:bg-white dark:text-zinc-950">Checkout</button>
            </form>
        </aside>
    </section>
</x-storefront.layout>

