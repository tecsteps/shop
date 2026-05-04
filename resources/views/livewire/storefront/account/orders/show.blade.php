<section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    <x-storefront.breadcrumbs :items="[
        ['label' => 'Account', 'url' => route('account.dashboard')],
        ['label' => $order->order_number],
    ]" />

    <div class="mt-8 grid gap-8 lg:grid-cols-[1fr_24rem]">
        <div class="space-y-6">
            <div>
                <h1 class="text-3xl font-semibold tracking-normal text-zinc-950 dark:text-white">{{ $order->order_number }}</h1>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $order->placed_at?->format('M j, Y') }}</p>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-950">
                <flux:heading size="lg">Items</flux:heading>
                <div class="mt-4 divide-y divide-zinc-200 dark:divide-zinc-800">
                    @foreach ($order->lines as $line)
                        <div class="flex items-center justify-between gap-4 py-3" wire:key="account-order-line-{{ $line->getKey() }}">
                            <div>
                                <p class="font-medium text-zinc-950 dark:text-white">{{ $line->title_snapshot }}</p>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">Qty {{ $line->quantity }}</p>
                            </div>
                            <x-storefront.price :amount="$line->total_amount" :currency="$order->currency" />
                        </div>
                    @endforeach
                </div>
            </div>

            @if ($order->fulfillments->isNotEmpty())
                <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-950">
                    <flux:heading size="lg">Fulfillment</flux:heading>
                    <div class="mt-4 space-y-3">
                        @foreach ($order->fulfillments as $fulfillment)
                            <div class="rounded-md border border-zinc-200 p-4 dark:border-zinc-800" wire:key="account-fulfillment-{{ $fulfillment->getKey() }}">
                                <div class="flex items-center justify-between gap-4">
                                    <span class="font-medium text-zinc-950 dark:text-white">{{ $fulfillment->status->value }}</span>
                                    @if ($fulfillment->tracking_number)
                                        <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ $fulfillment->tracking_number }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <aside class="h-fit rounded-lg border border-zinc-200 bg-zinc-50 p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="text-base font-semibold text-zinc-950 dark:text-white">Summary</h2>
            <div class="mt-4 flex flex-wrap gap-2">
                <flux:badge>{{ $order->financial_status->value }}</flux:badge>
                <flux:badge>{{ $order->fulfillment_status->value }}</flux:badge>
            </div>
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
                <div class="flex justify-between gap-4 border-t border-zinc-200 pt-3 text-base dark:border-zinc-800">
                    <span class="font-semibold text-zinc-950 dark:text-white">Total</span>
                    <x-storefront.price :amount="$order->total_amount" :currency="$order->currency" />
                </div>
            </div>
        </aside>
    </div>
</section>
