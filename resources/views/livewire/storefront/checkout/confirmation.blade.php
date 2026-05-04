<section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    @php
        $paymentMethodLabel = match ($order->payment_method) {
            \App\Enums\PaymentMethod::CreditCard => 'Credit Card',
            \App\Enums\PaymentMethod::Paypal => 'PayPal',
            \App\Enums\PaymentMethod::BankTransfer => 'Bank Transfer',
        };
    @endphp

    <x-storefront.breadcrumbs :items="[
        ['label' => 'Cart', 'url' => route('cart.show')],
        ['label' => 'Checkout', 'url' => route('checkout.show')],
        ['label' => 'Confirmation'],
    ]" />

    <div class="mt-8 grid gap-8 lg:grid-cols-[1fr_24rem]">
        <div class="space-y-6">
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-950">
                <flux:badge color="green">Order placed</flux:badge>
                <h1 class="mt-4 text-3xl font-semibold tracking-normal text-zinc-950 dark:text-white">
                    Thank you for your order!
                </h1>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                    Order {{ $order->order_number }} has been placed. Confirmation sent to {{ $order->email }}.
                </p>
            </div>

            @if ($order->payment_method === \App\Enums\PaymentMethod::BankTransfer)
                <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-950">
                    <flux:heading size="lg">Bank transfer</flux:heading>
                    <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-zinc-500 dark:text-zinc-400">Bank</dt>
                            <dd class="font-medium text-zinc-950 dark:text-white">Mock Bank AG</dd>
                        </div>
                        <div>
                            <dt class="text-zinc-500 dark:text-zinc-400">BIC</dt>
                            <dd class="font-medium text-zinc-950 dark:text-white">COBADEFFXXX</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-zinc-500 dark:text-zinc-400">IBAN</dt>
                            <dd class="font-medium text-zinc-950 dark:text-white">DE89 3704 0044 0532 0130 00</dd>
                        </div>
                        <div>
                            <dt class="text-zinc-500 dark:text-zinc-400">Reference</dt>
                            <dd class="font-medium text-zinc-950 dark:text-white">{{ $order->order_number }}</dd>
                        </div>
                        <div>
                            <dt class="text-zinc-500 dark:text-zinc-400">Amount</dt>
                            <dd class="font-medium text-zinc-950 dark:text-white">{{ \App\Support\Money::format($order->total_amount, $order->currency) }}</dd>
                        </div>
                    </dl>
                </div>
            @endif

            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-950">
                <flux:heading size="lg">Items</flux:heading>
                <div class="mt-4 divide-y divide-zinc-200 dark:divide-zinc-800">
                    @foreach ($order->lines as $line)
                        <div class="flex items-center justify-between gap-4 py-3" wire:key="confirmation-line-{{ $line->getKey() }}">
                            <div>
                                <p class="font-medium text-zinc-950 dark:text-white">{{ $line->title_snapshot }}</p>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">Qty {{ $line->quantity }}</p>
                            </div>
                            <x-storefront.price :amount="$line->total_amount" :currency="$order->currency" />
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <aside class="h-fit rounded-lg border border-zinc-200 bg-zinc-50 p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="text-base font-semibold text-zinc-950 dark:text-white">Summary</h2>
            <div class="mt-5 space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <span class="text-zinc-600 dark:text-zinc-400">Subtotal</span>
                    <x-storefront.price :amount="$order->subtotal_amount" :currency="$order->currency" />
                </div>
                <div class="flex justify-between gap-4">
                    <span class="text-zinc-600 dark:text-zinc-400">Discount</span>
                    <span class="font-semibold text-zinc-950 dark:text-white">-{{ \App\Support\Money::format($order->discount_amount, $order->currency) }}</span>
                </div>
                <div class="flex justify-between gap-4">
                    <span class="text-zinc-600 dark:text-zinc-400">Shipping</span>
                    <x-storefront.price :amount="$order->shipping_amount" :currency="$order->currency" />
                </div>
                <div class="flex justify-between gap-4">
                    <span class="text-zinc-600 dark:text-zinc-400">Tax</span>
                    <x-storefront.price :amount="$order->tax_amount" :currency="$order->currency" />
                </div>
                <div class="flex justify-between gap-4">
                    <span class="text-zinc-600 dark:text-zinc-400">Payment method</span>
                    <span class="font-medium text-zinc-950 dark:text-white">{{ $paymentMethodLabel }}</span>
                </div>
                <div class="flex justify-between gap-4 border-t border-zinc-200 pt-3 text-base dark:border-zinc-800">
                    <span class="font-semibold text-zinc-950 dark:text-white">Total</span>
                    <x-storefront.price :amount="$order->total_amount" :currency="$order->currency" />
                </div>
            </div>

            <flux:button :href="route('collections.index')" wire:navigate variant="primary" class="mt-6 w-full">
                Continue shopping
            </flux:button>
        </aside>
    </div>
</section>
