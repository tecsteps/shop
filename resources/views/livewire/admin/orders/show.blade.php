<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ $order->order_number }}</flux:heading>
        <div class="flex gap-2">
            <flux:badge :color="$order->financial_status->value === 'paid' ? 'emerald' : 'zinc'">{{ $order->financial_status->value }}</flux:badge>
            <flux:badge :color="$order->fulfillment_status->value === 'fulfilled' ? 'emerald' : 'zinc'">{{ $order->fulfillment_status->value }}</flux:badge>
        </div>
    </div>

    @if (session('success'))
        <flux:callout variant="success" icon="check-circle" heading="{{ session('success') }}"></flux:callout>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 flex flex-col gap-6">
            <section class="rounded-xl bg-white p-6 ring-1 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700">
                <flux:heading size="lg" class="mb-4">Items</flux:heading>
                <div class="space-y-3">
                    @foreach ($order->lines as $line)
                        <div class="flex justify-between rounded-lg bg-zinc-50 p-3 dark:bg-zinc-900">
                            <div>
                                <div class="font-medium">{{ $line->title_snapshot }}</div>
                                <div class="text-xs text-zinc-500">SKU: {{ $line->sku_snapshot }} · qty {{ $line->quantity }}</div>
                            </div>
                            <div>{{ $order->currency }} {{ number_format($line->total_amount / 100, 2) }}</div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-4 border-t border-zinc-200 pt-3 text-sm dark:border-zinc-700">
                    <div class="flex justify-between"><span>Subtotal</span><span>{{ $order->currency }} {{ number_format($order->subtotal_amount / 100, 2) }}</span></div>
                    @if ($order->discount_amount > 0)<div class="flex justify-between text-emerald-600"><span>Discount</span><span>−{{ $order->currency }} {{ number_format($order->discount_amount / 100, 2) }}</span></div>@endif
                    <div class="flex justify-between"><span>Shipping</span><span>{{ $order->currency }} {{ number_format($order->shipping_amount / 100, 2) }}</span></div>
                    <div class="flex justify-between"><span>Tax</span><span>{{ $order->currency }} {{ number_format($order->tax_amount / 100, 2) }}</span></div>
                    <div class="mt-2 flex justify-between border-t border-zinc-200 pt-2 font-semibold dark:border-zinc-700"><span>Total</span><span>{{ $order->currency }} {{ number_format($order->total_amount / 100, 2) }}</span></div>
                </div>
            </section>

            @if ($order->fulfillments->isNotEmpty())
                <section class="rounded-xl bg-white p-6 ring-1 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700">
                    <flux:heading size="lg" class="mb-4">Fulfillments</flux:heading>
                    @foreach ($order->fulfillments as $f)
                        <div class="flex justify-between text-sm">
                            <div>
                                <div class="font-medium">{{ $f->tracking_company }} · {{ $f->tracking_number ?: 'no tracking' }}</div>
                                <div class="text-xs text-zinc-500">{{ $f->shipped_at?->format('Y-m-d H:i') }}</div>
                            </div>
                            <flux:badge :color="$f->status->value === 'shipped' ? 'emerald' : 'zinc'">{{ $f->status->value }}</flux:badge>
                        </div>
                    @endforeach
                </section>
            @endif

            @if ($order->refunds->isNotEmpty())
                <section class="rounded-xl bg-white p-6 ring-1 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700">
                    <flux:heading size="lg" class="mb-4">Refunds</flux:heading>
                    @foreach ($order->refunds as $r)
                        <div class="flex justify-between text-sm">
                            <span>{{ $r->created_at?->format('Y-m-d H:i') }} · {{ $r->reason }}</span>
                            <span>{{ $order->currency }} {{ number_format($r->amount / 100, 2) }}</span>
                        </div>
                    @endforeach
                </section>
            @endif
        </div>

        <aside class="flex flex-col gap-4">
            <section class="rounded-xl bg-white p-6 ring-1 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700">
                <flux:heading size="lg" class="mb-3">Customer</flux:heading>
                <div class="text-sm">
                    <div class="font-medium">{{ $order->customer?->name ?? 'Guest' }}</div>
                    <div class="text-zinc-500">{{ $order->email }}</div>
                </div>
            </section>

            <section class="rounded-xl bg-white p-6 ring-1 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700">
                <flux:heading size="lg" class="mb-3">Addresses</flux:heading>
                <div class="text-sm">
                    <div class="mb-1 font-medium">Shipping</div>
                    @php($s = $order->shipping_address_json)
                    @if ($s)
                        <div>{{ $s['first_name'] ?? '' }} {{ $s['last_name'] ?? '' }}</div>
                        <div>{{ $s['address1'] ?? '' }}</div>
                        <div>{{ $s['zip'] ?? '' }} {{ $s['city'] ?? '' }}</div>
                        <div>{{ $s['country'] ?? '' }}</div>
                    @endif
                </div>
            </section>

            <section class="rounded-xl bg-white p-6 ring-1 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700">
                <flux:heading size="lg" class="mb-3">Actions</flux:heading>
                <div class="flex flex-col gap-3">
                    @if ($order->fulfillment_status->value !== 'fulfilled' && $order->status->value !== 'cancelled')
                        <div class="flex flex-col gap-2">
                            <flux:input size="sm" label="Tracking company" wire:model="trackingCompany" />
                            <flux:input size="sm" label="Tracking number" wire:model="trackingNumber" />
                            <flux:button size="sm" variant="primary" wire:click="fulfillAll">Mark fulfilled</flux:button>
                        </div>
                    @endif

                    @if ($order->financial_status->value === 'paid')
                        <div class="flex flex-col gap-2 border-t border-zinc-200 pt-3 dark:border-zinc-700">
                            <flux:input type="number" size="sm" label="Refund amount (cents)" wire:model="refundAmount" placeholder="Full refund" />
                            <flux:button size="sm" variant="ghost" wire:click="refund">Refund</flux:button>
                        </div>
                    @endif

                    @if ($order->status->value === 'open')
                        <flux:button size="sm" variant="danger" wire:click="cancel" wire:confirm="Cancel this order?">Cancel order</flux:button>
                    @endif
                </div>
            </section>
        </aside>
    </div>
</div>
