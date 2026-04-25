<x-storefront.layout :title="'Checkout'">
    <section class="mx-auto grid max-w-7xl gap-8 px-4 py-10 lg:grid-cols-[1fr_360px]">
        <form method="POST" action="{{ route('checkout.update', $checkout) }}" class="grid gap-8">
            @csrf
            <div class="rounded-lg border border-zinc-200 p-5 dark:border-zinc-800">
                <h1 class="text-2xl font-bold tracking-normal">Checkout</h1>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <label class="grid gap-2 sm:col-span-2"><span>Email</span><input name="email" type="email" value="{{ old('email', $checkout->email ?? auth('customer')->user()?->email) }}" class="rounded-md border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900"></label>
                    <label class="grid gap-2 sm:col-span-2"><span>Name</span><input name="name" value="{{ old('name', $checkout->shipping_address_json['name'] ?? auth('customer')->user()?->name) }}" class="rounded-md border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900"></label>
                    <label class="grid gap-2 sm:col-span-2"><span>Address</span><input name="address1" value="{{ old('address1', $checkout->shipping_address_json['address1'] ?? '') }}" class="rounded-md border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900"></label>
                    <label class="grid gap-2"><span>City</span><input name="city" value="{{ old('city', $checkout->shipping_address_json['city'] ?? 'Berlin') }}" class="rounded-md border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900"></label>
                    <label class="grid gap-2"><span>Postal code</span><input name="postal_code" value="{{ old('postal_code', $checkout->shipping_address_json['postal_code'] ?? '10115') }}" class="rounded-md border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900"></label>
                    <label class="grid gap-2"><span>Country code</span><input name="country_code" maxlength="2" value="{{ old('country_code', $checkout->shipping_address_json['country_code'] ?? 'DE') }}" class="rounded-md border border-zinc-300 px-3 py-2 uppercase dark:border-zinc-700 dark:bg-zinc-900"></label>
                </div>
            </div>
            <div class="rounded-lg border border-zinc-200 p-5 dark:border-zinc-800">
                <h2 class="text-xl font-semibold">Payment</h2>
                <div class="mt-4 grid gap-3">
                    <label class="flex items-center gap-2"><input type="radio" name="payment_method" value="credit_card" checked> Credit card</label>
                    <label class="flex items-center gap-2"><input type="radio" name="payment_method" value="paypal"> PayPal</label>
                    <label class="flex items-center gap-2"><input type="radio" name="payment_method" value="bank_transfer"> Bank transfer</label>
                    <label class="grid gap-2"><span>Card number</span><input name="card_number" value="{{ old('card_number', '4242 4242 4242 4242') }}" class="rounded-md border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900"></label>
                </div>
            </div>
            <button class="rounded-md bg-zinc-950 px-5 py-3 font-medium text-white dark:bg-white dark:text-zinc-950">Place order</button>
        </form>
        <aside class="h-max rounded-lg border border-zinc-200 p-5 dark:border-zinc-800">
            <h2 class="font-semibold">Summary</h2>
            <div class="mt-4 grid gap-3">
                @foreach($checkout->cart->lines as $line)
                    <div class="flex justify-between gap-4 text-sm">
                        <span>{{ $line->quantity }} x {{ $line->snapshot_json['product_title'] }}</span>
                        <span><x-shop.price :amount="$line->quantity * $line->unit_price_amount" :currency="$checkout->cart->currency" /></span>
                    </div>
                @endforeach
            </div>
            <dl class="mt-4 grid gap-2 border-t border-zinc-200 pt-4 text-sm dark:border-zinc-800">
                <div class="flex justify-between"><dt>Subtotal</dt><dd><x-shop.price :amount="$totals['subtotal']" :currency="$totals['currency']" /></dd></div>
                <div class="flex justify-between"><dt>Discount</dt><dd>-<x-shop.price :amount="$totals['discount']" :currency="$totals['currency']" /></dd></div>
                <div class="flex justify-between"><dt>Shipping</dt><dd><x-shop.price :amount="$totals['shipping']" :currency="$totals['currency']" /></dd></div>
                <div class="flex justify-between"><dt>Tax</dt><dd><x-shop.price :amount="$totals['tax']" :currency="$totals['currency']" /></dd></div>
                <div class="flex justify-between text-base font-semibold"><dt>Total</dt><dd><x-shop.price :amount="$totals['total']" :currency="$totals['currency']" /></dd></div>
            </dl>
        </aside>
    </section>
</x-storefront.layout>

