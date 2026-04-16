<div class="grid grid-cols-1 gap-10 lg:grid-cols-3">
    <form wire:submit="placeOrder" class="flex flex-col gap-6 lg:col-span-2" data-testid="checkout-form">
        @if ($errorMessage)
            <flux:callout variant="danger" icon="exclamation-triangle" heading="{{ $errorMessage }}"></flux:callout>
        @endif

        <section class="rounded-xl bg-white p-6 ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-zinc-800">
            <flux:heading size="lg" class="mb-4">Contact</flux:heading>
            <flux:input type="email" label="Email" wire:model="email" required />
        </section>

        <section class="rounded-xl bg-white p-6 ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-zinc-800">
            <flux:heading size="lg" class="mb-4">Shipping address</flux:heading>
            <div class="grid grid-cols-2 gap-3">
                <flux:input label="First name" wire:model="firstName" required />
                <flux:input label="Last name" wire:model="lastName" required />
                <div class="col-span-2">
                    <flux:input label="Street address" wire:model="address1" required />
                </div>
                <flux:input label="City" wire:model="city" required />
                <flux:input label="Postal code" wire:model="zip" required />
                <div class="col-span-2">
                    <flux:select label="Country" wire:model.live="country" required>
                        <flux:select.option value="DE">Germany</flux:select.option>
                        <flux:select.option value="AT">Austria</flux:select.option>
                        <flux:select.option value="CH">Switzerland</flux:select.option>
                        <flux:select.option value="FR">France</flux:select.option>
                        <flux:select.option value="NL">Netherlands</flux:select.option>
                        <flux:select.option value="US">United States</flux:select.option>
                    </flux:select>
                </div>
            </div>
        </section>

        <section class="rounded-xl bg-white p-6 ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-zinc-800">
            <flux:heading size="lg" class="mb-4">Shipping method</flux:heading>
            @if ($rates->isEmpty())
                <div class="text-sm text-zinc-500">No shipping rates available for this country.</div>
            @else
                <div class="flex flex-col gap-2" data-testid="shipping-rates">
                    @foreach ($rates as $rate)
                        <label class="flex cursor-pointer items-center justify-between rounded-lg border border-zinc-200 p-3 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800/50">
                            <div class="flex items-center gap-3">
                                <input type="radio" wire:model.live="shippingRateId" value="{{ $rate->id }}" class="text-zinc-900">
                                <span class="font-medium">{{ $rate->name }}</span>
                            </div>
                            <span class="text-sm">{{ $currentStore->default_currency }} {{ number_format($rate->baseAmount() / 100, 2) }}</span>
                        </label>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="rounded-xl bg-white p-6 ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-zinc-800">
            <flux:heading size="lg" class="mb-4">Payment</flux:heading>
            <div class="mb-4 flex gap-3">
                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-800">
                    <input type="radio" wire:model.live="paymentMethod" value="credit_card">
                    <span>Credit card</span>
                </label>
                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-800">
                    <input type="radio" wire:model.live="paymentMethod" value="paypal">
                    <span>PayPal</span>
                </label>
                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-800">
                    <input type="radio" wire:model.live="paymentMethod" value="bank_transfer">
                    <span>Bank transfer</span>
                </label>
            </div>

            @if ($paymentMethod === 'credit_card')
                <div class="grid grid-cols-2 gap-3">
                    <div class="col-span-2">
                        <flux:input label="Card number" wire:model="cardNumber" placeholder="4242 4242 4242 4242" />
                    </div>
                    <div class="col-span-2">
                        <flux:input label="Cardholder name" wire:model="cardName" />
                    </div>
                    <flux:input label="Expiry (MM/YY)" wire:model="cardExpiry" />
                    <flux:input label="CVC" wire:model="cardCvc" />
                </div>
                <div class="mt-3 text-xs text-zinc-500">
                    Use 4242 4242 4242 4242 for success, 4000 0000 0000 0002 for decline.
                </div>
            @elseif ($paymentMethod === 'paypal')
                <div class="rounded-lg bg-zinc-50 p-4 text-sm text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">Mock PayPal - order will be placed immediately.</div>
            @else
                <div class="rounded-lg bg-zinc-50 p-4 text-sm text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">Pay by bank transfer - order placed as authorized pending transfer.</div>
            @endif
        </section>

        <flux:button type="submit" variant="primary" class="w-full" data-testid="place-order">
            Place order
        </flux:button>
    </form>

    <aside class="rounded-xl bg-white p-6 ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-zinc-800">
        <flux:heading size="lg" class="mb-4">Order summary</flux:heading>
        <div class="flex flex-col gap-3">
            @foreach ($cart->lines as $line)
                <div class="flex justify-between text-sm">
                    <div>
                        <div class="font-medium">{{ $line->variant->product->title }}</div>
                        <div class="text-xs text-zinc-500">× {{ $line->quantity }}</div>
                    </div>
                    <div>{{ $cart->currency }} {{ number_format($line->line_total_amount / 100, 2) }}</div>
                </div>
            @endforeach
        </div>
        <div class="mt-4 border-t border-zinc-200 pt-4 text-sm dark:border-zinc-800">
            <div class="flex justify-between"><span>Subtotal</span><span>{{ $totals['currency'] ?? $cart->currency }} {{ number_format(($totals['subtotal'] ?? 0) / 100, 2) }}</span></div>
            @if (($totals['discount'] ?? 0) > 0)
                <div class="flex justify-between text-emerald-600"><span>Discount</span><span>−{{ $totals['currency'] }} {{ number_format($totals['discount'] / 100, 2) }}</span></div>
            @endif
            <div class="flex justify-between"><span>Shipping</span><span>{{ $totals['currency'] ?? $cart->currency }} {{ number_format(($totals['shipping'] ?? 0) / 100, 2) }}</span></div>
            <div class="flex justify-between"><span>Tax</span><span>{{ $totals['currency'] ?? $cart->currency }} {{ number_format(($totals['tax'] ?? 0) / 100, 2) }}</span></div>
            <div class="mt-2 flex justify-between border-t border-zinc-200 pt-2 text-base font-semibold dark:border-zinc-800">
                <span>Total</span><span data-testid="checkout-total">{{ $totals['currency'] ?? $cart->currency }} {{ number_format(($totals['total'] ?? 0) / 100, 2) }}</span>
            </div>
        </div>
    </aside>
</div>
