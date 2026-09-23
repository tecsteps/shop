<div>
    @include('livewire.admin.components.page-heading', ['title' => 'Order #'.$order->order_number, 'breadcrumbs' => [['label' => 'Orders', 'href' => '/admin/orders'], ['label' => '#'.$order->order_number]]])
    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_21rem]">
        <div class="grid content-start gap-6">
            <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900" aria-labelledby="order-items-heading"><div class="flex flex-wrap items-start justify-between gap-3 border-b border-zinc-200 px-5 py-4 dark:border-zinc-800"><div><h2 id="order-items-heading" class="font-semibold">Items</h2><p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Placed {{ $order->placed_at?->format('F j, Y · g:i A') ?? $order->created_at?->format('F j, Y · g:i A') }}</p></div><div class="flex flex-wrap gap-2">@include('livewire.admin.components.status-badge', ['status' => $order->status])@include('livewire.admin.components.status-badge', ['status' => $order->financial_status])</div></div>@if($order->lines->count())<div class="divide-y divide-zinc-100 px-5 dark:divide-zinc-800">@foreach($order->lines as $line)<div wire:key="admin-order-line-{{ $line->id }}" class="flex flex-wrap items-center justify-between gap-4 py-4"><div class="min-w-0"><p class="font-medium">{{ $line->title_snapshot }}</p><p class="mt-1 text-xs text-zinc-500">{{ $line->variant_title_snapshot }}@if($line->sku_snapshot) · SKU {{ $line->sku_snapshot }}@endif · Qty {{ $line->quantity }}</p></div><p class="shrink-0 text-sm font-medium tabular-nums">{{ number_format($line->total_amount / 100, 2) }} {{ data_get(app()->bound('current_store') ? app('current_store') : null, 'default_currency', 'EUR') }}</p></div>@endforeach</div>@else<div class="px-5 py-8 text-sm text-zinc-500">No line items are available.</div>@endif<div class="border-t border-zinc-200 px-5 py-4 dark:border-zinc-800"><dl class="ml-auto grid max-w-sm gap-2 text-sm"><div class="flex justify-between gap-4"><dt class="text-zinc-500">Subtotal</dt><dd>{{ number_format($order->subtotal_amount / 100, 2) }}</dd></div><div class="flex justify-between gap-4"><dt class="text-zinc-500">Shipping</dt><dd>{{ number_format($order->shipping_amount / 100, 2) }}</dd></div><div class="flex justify-between gap-4"><dt class="text-zinc-500">Tax</dt><dd>{{ number_format($order->tax_amount / 100, 2) }}</dd></div>@if($order->discount_amount > 0)<div class="flex justify-between gap-4 text-emerald-700 dark:text-emerald-300"><dt>Discount</dt><dd>−{{ number_format($order->discount_amount / 100, 2) }}</dd></div>@endif<div class="flex justify-between gap-4 border-t border-zinc-200 pt-3 text-base font-semibold dark:border-zinc-800"><dt>Total</dt><dd>{{ number_format($order->total_amount / 100, 2) }} {{ data_get(app()->bound('current_store') ? app('current_store') : null, 'default_currency', 'EUR') }}</dd></div></dl></div></section>
            <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900" aria-labelledby="order-fulfillment-heading">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 id="order-fulfillment-heading" class="font-semibold">Fulfillment</h2>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Create shipments for selected quantities, then record when each shipment leaves and arrives.</p>
                    </div>
                    @include('livewire.admin.components.status-badge', ['status' => $order->fulfillment_status ?: 'unfulfilled'])
                </div>

                @if($order->fulfillments->isNotEmpty())
                    <div class="mt-4 grid gap-3">
                        @foreach($order->fulfillments as $fulfillment)
                            <article wire:key="admin-order-fulfillment-{{ $fulfillment->id }}" class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-950">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold">{{ $fulfillment->tracking_company ?: 'Shipment' }}</p>
                                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ $fulfillment->tracking_number ?: 'Tracking number not provided' }}</p>
                                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Created {{ $fulfillment->created_at?->format('M j, Y · g:i A') }}</p>
                                        @if($fulfillment->shipped_at)<p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Shipped {{ $fulfillment->shipped_at->format('M j, Y · g:i A') }}</p>@endif
                                        @if($fulfillment->delivered_at)<p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Delivered {{ $fulfillment->delivered_at->format('M j, Y · g:i A') }}</p>@endif
                                    </div>
                                    @include('livewire.admin.components.status-badge', ['status' => $fulfillment->status])
                                </div>

                                @if($fulfillment->tracking_url)
                                    <a href="{{ $fulfillment->tracking_url }}" target="_blank" rel="noopener noreferrer" class="mt-3 inline-flex min-h-9 items-center gap-1 text-sm font-medium text-blue-700 underline dark:text-blue-300">Track shipment (opens in new tab) <flux:icon name="arrow-top-right-on-square" class="size-4" /></a>
                                @endif

                                @if($fulfillment->lines->isNotEmpty())
                                    <ul class="mt-3 grid gap-1 border-t border-zinc-200 pt-3 text-sm text-zinc-600 dark:border-zinc-800 dark:text-zinc-300">
                                        @foreach($fulfillment->lines as $fulfillmentLine)
                                            <li>{{ $fulfillmentLine->orderLine?->title_snapshot ?? 'Order item' }} · Qty {{ $fulfillmentLine->quantity }}</li>
                                        @endforeach
                                    </ul>
                                @endif

                                @can('update', $order)
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @if($fulfillment->status === 'pending')
                                            <button type="button" wire:click="markAsShipped({{ $fulfillment->id }})" wire:loading.attr="disabled" wire:target="markAsShipped({{ $fulfillment->id }})" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-blue-600 px-3 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60">
                                                <span wire:loading.remove wire:target="markAsShipped({{ $fulfillment->id }})">Mark as shipped</span>
                                                <span wire:loading wire:target="markAsShipped({{ $fulfillment->id }})">Updating…</span>
                                            </button>
                                        @elseif($fulfillment->status === 'shipped')
                                            <button type="button" wire:click="markAsDelivered({{ $fulfillment->id }})" wire:loading.attr="disabled" wire:target="markAsDelivered({{ $fulfillment->id }})" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-emerald-700 px-3 text-sm font-semibold text-white hover:bg-emerald-800 disabled:opacity-60">
                                                <span wire:loading.remove wire:target="markAsDelivered({{ $fulfillment->id }})">Mark as delivered</span>
                                                <span wire:loading wire:target="markAsDelivered({{ $fulfillment->id }})">Updating…</span>
                                            </button>
                                        @endif
                                    </div>
                                @endcan
                            </article>
                        @endforeach
                    </div>
                @else
                    <p class="mt-4 rounded-xl bg-zinc-50 px-4 py-5 text-sm text-zinc-600 dark:bg-zinc-950 dark:text-zinc-400">No shipments have been created for this order.</p>
                @endif

                @if(in_array($order->financial_status, ['paid', 'partially_refunded'], true) && !in_array($order->status, ['cancelled', 'canceled', 'refunded'], true) && $order->fulfillment_status !== 'fulfilled' && $order->lines->contains(fn ($line) => (int) $line->fulfillmentLines->sum('quantity') < $line->quantity))
                    @can('createFulfillment', $order)
                        <form wire:submit="fulfill" class="mt-5 grid gap-4 border-t border-zinc-200 pt-5 dark:border-zinc-800">
                            <div>
                                <h3 class="text-sm font-semibold">Create fulfillment</h3>
                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Choose the quantity of each remaining item to include in this shipment.</p>
                            </div>

                            @if($order->lines->isNotEmpty())
                                <div class="grid gap-3">
                                    @foreach($order->lines as $line)
                                        @php($remainingQuantity = max(0, $line->quantity - (int) $line->fulfillmentLines->sum('quantity')))
                                        <div class="grid gap-2 rounded-xl border border-zinc-200 p-3 sm:grid-cols-[minmax(0,1fr)_8rem] sm:items-center dark:border-zinc-800">
                                            <div>
                                                <label for="fulfillment-quantity-{{ $line->id }}" class="block text-sm font-medium">{{ $line->title_snapshot }}</label>
                                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $line->variant_title_snapshot }} · {{ $remainingQuantity }} remaining of {{ $line->quantity }}</p>
                                            </div>
                                            <input id="fulfillment-quantity-{{ $line->id }}" type="number" min="0" max="{{ $remainingQuantity }}" step="1" inputmode="numeric" wire:model.number="fulfillmentLines.{{ $line->id }}" @disabled($remainingQuantity === 0) aria-label="Quantity to fulfill for {{ $line->title_snapshot }}" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 disabled:bg-zinc-100 disabled:text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900 dark:disabled:bg-zinc-800">
                                            @error("fulfillmentLines.{$line->id}")<p class="text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="rounded-xl bg-zinc-50 px-4 py-5 text-sm text-zinc-600 dark:bg-zinc-950 dark:text-zinc-400">There are no order items available to fulfill.</p>
                            @endif

                            @error('fulfillmentLines')<p class="text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
                            @error('lines')<p class="text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="tracking-company" class="mb-1.5 block text-sm font-medium">Carrier</label>
                                    <input id="tracking-company" type="text" wire:model="trackingCompany" placeholder="DHL" autocomplete="organization" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-950">
                                    @error('trackingCompany')<p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="tracking-number" class="mb-1.5 block text-sm font-medium">Tracking number</label>
                                    <input id="tracking-number" type="text" wire:model="trackingNumber" placeholder="Tracking number" autocomplete="off" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-950">
                                    @error('trackingNumber')<p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
                                </div>
                                <div class="sm:col-span-2">
                                    <label for="tracking-url" class="mb-1.5 block text-sm font-medium">Tracking link <span class="font-normal text-zinc-500">(optional)</span></label>
                                    <input id="tracking-url" type="url" wire:model="trackingUrl" placeholder="https://carrier.example/track/…" inputmode="url" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-950">
                                    @error('trackingUrl')<p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <button type="submit" wire:loading.attr="disabled" wire:target="fulfill" class="inline-flex min-h-11 items-center justify-center gap-2 justify-self-start rounded-xl bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60">
                                <span wire:loading.remove wire:target="fulfill">Create fulfillment</span>
                                <span wire:loading wire:target="fulfill">Creating fulfillment…</span>
                            </button>
                        </form>
                    @endcan
                @endif
            </section>
            @if($order->payments->count())<section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"><h2 class="font-semibold">Payments</h2><div class="mt-4 divide-y divide-zinc-100 dark:divide-zinc-800">@foreach($order->payments as $payment)<div class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0"><div><p class="text-sm font-medium">{{ \Illuminate\Support\Str::headline($payment->method ?? 'Payment') }}</p><p class="mt-1 text-xs text-zinc-500">{{ $payment->created_at?->format('M j, Y · g:i A') }}</p></div><div class="flex items-center gap-3">@include('livewire.admin.components.status-badge', ['status' => $payment->status])<span class="text-sm font-medium tabular-nums">{{ number_format($payment->amount / 100, 2) }}</span></div></div>@endforeach</div></section>@endif
            @if($order->refunds->count())<section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"><h2 class="font-semibold">Refunds</h2><div class="mt-4 grid gap-3">@foreach($order->refunds as $refund)<div class="flex items-start justify-between gap-4 rounded-xl bg-zinc-50 p-4 dark:bg-zinc-950"><div><p class="text-sm font-medium">{{ $refund->reason ?: 'Refund' }}</p><p class="mt-1 text-xs text-zinc-500">{{ $refund->created_at?->format('M j, Y · g:i A') }}</p></div><p class="text-sm font-semibold tabular-nums">−{{ number_format($refund->amount / 100, 2) }}</p></div>@endforeach</div></section>@endif
        </div>
        <aside class="grid content-start gap-6">
            <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"><h2 class="font-semibold">Customer</h2><p class="mt-4 text-sm font-medium">{{ $order->customer?->name ?: 'Guest customer' }}</p><a href="mailto:{{ $order->email }}" class="mt-1 block break-all text-sm text-blue-700 underline dark:text-blue-300">{{ $order->email }}</a>@if($order->customer)<a href="{{ route('admin.customers.show', ['customer' => $order->customer->id]) }}" wire:navigate class="mt-3 inline-flex min-h-10 items-center text-sm font-semibold text-blue-700 hover:underline dark:text-blue-300">View customer profile <flux:icon name="arrow-right" class="ml-1 size-4" /></a>@endif</section>
            @if($order->shipping_address_json)<section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"><h2 class="font-semibold">Shipping address</h2><address class="mt-4 text-sm not-italic leading-6 text-zinc-600 dark:text-zinc-300">{{ data_get($order->shipping_address_json, 'first_name') }} {{ data_get($order->shipping_address_json, 'last_name') }}<br>{{ data_get($order->shipping_address_json, 'address_line_1') }}@if(data_get($order->shipping_address_json, 'address_line_2'))<br>{{ data_get($order->shipping_address_json, 'address_line_2') }}@endif<br>{{ data_get($order->shipping_address_json, 'city') }}, {{ data_get($order->shipping_address_json, 'state') }} {{ data_get($order->shipping_address_json, 'postal_code') }}<br>{{ data_get($order->shipping_address_json, 'country') }}</address></section>@endif
            <section class="grid gap-2 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"><h2 class="mb-2 font-semibold">Order actions</h2>@if($order->financial_status === 'pending')<button type="button" wire:click="confirmPayment" wire:confirm="Mark payment for this order as received?" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-zinc-300 px-4 text-sm font-semibold hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">Confirm payment</button>@endif @if($order->status === 'pending')<button type="button" wire:click="cancel" wire:confirm="Cancel this pending order?" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-rose-200 px-4 text-sm font-semibold text-rose-700 hover:bg-rose-50 dark:border-rose-900 dark:text-rose-300 dark:hover:bg-rose-950/40">Cancel order</button>@endif</section>
            @if(in_array($order->financial_status, ['paid', 'partially_refunded'], true))<form wire:submit="refund" class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"><h2 class="font-semibold">Issue a refund</h2><p class="mt-1 text-xs leading-5 text-zinc-500 dark:text-zinc-400">Enter an amount in minor units. For example, 1250 = 12.50.</p><div class="mt-4 grid gap-3"><div><label for="refund-amount" class="mb-1.5 block text-sm font-medium">Amount (minor units)</label><input id="refund-amount" type="number" min="1" max="{{ $order->total_amount }}" wire:model="refundAmount" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-950">@error('refundAmount')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror</div><div><label for="refund-reason" class="mb-1.5 block text-sm font-medium">Reason</label><textarea id="refund-reason" rows="2" wire:model="refundReason" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-950"></textarea>@error('refundReason')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror</div><label class="flex min-h-10 items-center gap-3 text-sm"><input type="checkbox" wire:model="restock" class="size-4 rounded border-zinc-300 text-blue-600 focus:ring-blue-600">Restock refunded items</label><button type="submit" wire:loading.attr="disabled" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-rose-700 px-4 text-sm font-semibold text-white hover:bg-rose-800 disabled:opacity-60"><span wire:loading.remove wire:target="refund">Issue refund</span><span wire:loading wire:target="refund">Processing…</span></button></div></form>@endif
        </aside>
    </div>
</div>
