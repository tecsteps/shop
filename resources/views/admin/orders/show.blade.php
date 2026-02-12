@extends('admin.layout')

@section('title', 'Order '.$order->order_number)

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900">Order {{ $order->order_number }}</h1>
            <p class="mt-1 text-sm text-zinc-600">
                Placed {{ optional($order->placed_at)->format('M j, Y g:i A') ?? '-' }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2 text-sm">
            <span class="rounded-full border border-zinc-300 bg-white px-3 py-1">Status: {{ $order->status->value }}</span>
            <span class="rounded-full border border-zinc-300 bg-white px-3 py-1">Financial: {{ $order->financial_status->value }}</span>
            <span class="rounded-full border border-zinc-300 bg-white px-3 py-1">Fulfillment: {{ $order->fulfillment_status->value }}</span>
        </div>
    </div>

    @if ($order->payment_method === \App\Enums\PaymentMethod::BankTransfer && $order->financial_status === \App\Enums\FinancialStatus::Pending)
        <form method="POST" action="{{ route('admin.orders.confirm-bank-transfer', $order) }}" class="mb-6">
            @csrf
            <button type="submit" class="rounded-md bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700">
                Confirm Bank Transfer Payment
            </button>
        </form>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="overflow-hidden rounded-lg border border-zinc-200 bg-white">
                <div class="border-b border-zinc-200 px-4 py-3">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-700">Order Lines</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 text-sm">
                        <thead class="bg-zinc-50 text-left text-xs uppercase tracking-wide text-zinc-500">
                            <tr>
                                <th class="px-4 py-3">Item</th>
                                <th class="px-4 py-3">Qty</th>
                                <th class="px-4 py-3">Unit</th>
                                <th class="px-4 py-3 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 bg-white">
                            @foreach ($order->lines as $line)
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-zinc-900">{{ $line->title_snapshot }}</div>
                                        <div class="text-xs text-zinc-500">SKU: {{ $line->sku_snapshot ?: '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3">{{ $line->quantity }}</td>
                                    <td class="px-4 py-3">{{ strtoupper($order->currency) }} {{ number_format($line->unit_price_amount / 100, 2) }}</td>
                                    <td class="px-4 py-3 text-right">{{ strtoupper($order->currency) }} {{ number_format($line->total_amount / 100, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="space-y-2 border-t border-zinc-200 px-4 py-4 text-sm">
                    <div class="flex items-center justify-between"><span class="text-zinc-600">Subtotal</span><span>{{ strtoupper($order->currency) }} {{ number_format($order->subtotal_amount / 100, 2) }}</span></div>
                    <div class="flex items-center justify-between"><span class="text-zinc-600">Discount</span><span>-{{ strtoupper($order->currency) }} {{ number_format($order->discount_amount / 100, 2) }}</span></div>
                    <div class="flex items-center justify-between"><span class="text-zinc-600">Shipping</span><span>{{ strtoupper($order->currency) }} {{ number_format($order->shipping_amount / 100, 2) }}</span></div>
                    <div class="flex items-center justify-between"><span class="text-zinc-600">Tax</span><span>{{ strtoupper($order->currency) }} {{ number_format($order->tax_amount / 100, 2) }}</span></div>
                    <div class="flex items-center justify-between border-t border-zinc-200 pt-2 font-semibold"><span>Total</span><span>{{ strtoupper($order->currency) }} {{ number_format($order->total_amount / 100, 2) }}</span></div>
                </div>
            </section>

            <section class="rounded-lg border border-zinc-200 bg-white p-5">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-700">Create Fulfillment</h2>

                @if (! in_array($order->financial_status, [\App\Enums\FinancialStatus::Paid, \App\Enums\FinancialStatus::PartiallyRefunded], true))
                    <p class="mt-3 rounded-md border border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                        Fulfillment is disabled until payment is paid or partially refunded.
                    </p>
                @else
                    <form method="POST" action="{{ route('admin.orders.fulfillments.store', $order) }}" class="mt-4 space-y-4">
                        @csrf

                        <div class="space-y-2">
                            @foreach ($order->lines as $line)
                                @php
                                    $fulfilled = (int) ($fulfilledByLine[$line->id] ?? 0);
                                    $remaining = max($line->quantity - $fulfilled, 0);
                                @endphp

                                @if ($remaining > 0)
                                    <label class="flex items-center justify-between gap-3 rounded-md border border-zinc-200 px-3 py-2 text-sm">
                                        <span>
                                            <span class="block font-medium text-zinc-900">{{ $line->title_snapshot }}</span>
                                            <span class="block text-xs text-zinc-500">Remaining: {{ $remaining }}</span>
                                        </span>

                                        <input
                                            type="number"
                                            min="0"
                                            max="{{ $remaining }}"
                                            name="lines[{{ $line->id }}]"
                                            value="{{ old('lines.'.$line->id, $remaining) }}"
                                            class="w-24 rounded-md border border-zinc-300 px-2 py-1"
                                        >
                                    </label>
                                @endif
                            @endforeach
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3">
                            <label class="block text-sm">
                                <span class="mb-1 block text-zinc-700">Tracking company</span>
                                <input type="text" name="tracking_company" value="{{ old('tracking_company') }}" class="w-full rounded-md border border-zinc-300 px-3 py-2">
                            </label>
                            <label class="block text-sm">
                                <span class="mb-1 block text-zinc-700">Tracking number</span>
                                <input type="text" name="tracking_number" value="{{ old('tracking_number') }}" class="w-full rounded-md border border-zinc-300 px-3 py-2">
                            </label>
                            <label class="block text-sm">
                                <span class="mb-1 block text-zinc-700">Tracking URL</span>
                                <input type="url" name="tracking_url" value="{{ old('tracking_url') }}" class="w-full rounded-md border border-zinc-300 px-3 py-2">
                            </label>
                        </div>

                        <button type="submit" class="rounded-md bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700">
                            Create Fulfillment
                        </button>
                    </form>
                @endif
            </section>

            <section class="rounded-lg border border-zinc-200 bg-white p-5">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-700">Create Refund</h2>
                <p class="mt-2 text-sm text-zinc-600">Maximum refundable amount: {{ strtoupper($order->currency) }} {{ number_format($maxRefundable / 100, 2) }}</p>

                <form method="POST" action="{{ route('admin.orders.refunds.store', $order) }}" class="mt-4 space-y-4">
                    @csrf

                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="block text-sm">
                            <span class="mb-1 block text-zinc-700">Amount (minor units)</span>
                            <input type="number" min="1" max="{{ $maxRefundable }}" name="amount" value="{{ old('amount') }}" class="w-full rounded-md border border-zinc-300 px-3 py-2">
                            <span class="mt-1 block text-xs text-zinc-500">Leave empty to refund all remaining.</span>
                        </label>

                        <label class="block text-sm">
                            <span class="mb-1 block text-zinc-700">Reason</span>
                            <input type="text" name="reason" value="{{ old('reason') }}" class="w-full rounded-md border border-zinc-300 px-3 py-2">
                        </label>
                    </div>

                    <div class="space-y-2">
                        <p class="text-sm font-medium text-zinc-700">Optional restock quantities</p>
                        @foreach ($order->lines as $line)
                            <label class="flex items-center justify-between gap-3 rounded-md border border-zinc-200 px-3 py-2 text-sm">
                                <span class="font-medium text-zinc-900">{{ $line->title_snapshot }}</span>
                                <input
                                    type="number"
                                    min="0"
                                    max="{{ $line->quantity }}"
                                    name="lines[{{ $line->id }}]"
                                    value="{{ old('lines.'.$line->id, 0) }}"
                                    class="w-24 rounded-md border border-zinc-300 px-2 py-1"
                                >
                            </label>
                        @endforeach

                        <label class="inline-flex items-center gap-2 text-sm text-zinc-700">
                            <input type="checkbox" name="restock" value="1" @checked(old('restock')) class="rounded border-zinc-300 text-zinc-900">
                            Restock selected quantities
                        </label>
                    </div>

                    <button type="submit" class="rounded-md border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100">
                        Create Refund
                    </button>
                </form>
            </section>

            <section class="rounded-lg border border-zinc-200 bg-white p-5">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-700">Fulfillments & Refunds</h2>

                <div class="mt-4 space-y-4">
                    <div>
                        <h3 class="text-sm font-semibold text-zinc-800">Fulfillments</h3>
                        <div class="mt-2 space-y-2">
                            @forelse ($order->fulfillments as $fulfillment)
                                <div class="rounded-md border border-zinc-200 px-3 py-2 text-sm">
                                    <div class="font-medium text-zinc-900">#{{ $fulfillment->id }} · {{ $fulfillment->status->value }}</div>
                                    <div class="text-xs text-zinc-500">{{ $fulfillment->tracking_company }} {{ $fulfillment->tracking_number }}</div>
                                </div>
                            @empty
                                <p class="text-sm text-zinc-500">No fulfillments yet.</p>
                            @endforelse
                        </div>
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold text-zinc-800">Refunds</h3>
                        <div class="mt-2 space-y-2">
                            @forelse ($order->refunds as $refund)
                                <div class="rounded-md border border-zinc-200 px-3 py-2 text-sm">
                                    <div class="font-medium text-zinc-900">#{{ $refund->id }} · {{ $refund->status->value }}</div>
                                    <div class="text-xs text-zinc-500">{{ strtoupper($order->currency) }} {{ number_format($refund->amount / 100, 2) }} · {{ $refund->reason ?: 'No reason' }}</div>
                                </div>
                            @empty
                                <p class="text-sm text-zinc-500">No refunds yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div class="space-y-6">
            <section class="rounded-lg border border-zinc-200 bg-white p-5">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-700">Customer</h2>

                <div class="mt-3 text-sm">
                    <p class="font-medium text-zinc-900">{{ $order->customer?->name ?? 'Guest' }}</p>
                    <p class="text-zinc-600">{{ $order->email }}</p>
                    @if ($order->customer)
                        <a href="{{ route('admin.customers.show', $order->customer) }}" class="mt-2 inline-flex text-sm font-medium text-zinc-900 hover:underline">View customer</a>
                    @endif
                </div>
            </section>

            <section class="rounded-lg border border-zinc-200 bg-white p-5">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-700">Addresses</h2>

                <div class="mt-3 space-y-4 text-sm">
                    <div>
                        <p class="font-medium text-zinc-800">Shipping</p>
                        @if (is_array($order->shipping_address_json))
                            @foreach ($order->shipping_address_json as $value)
                                @if (! empty($value))
                                    <p class="text-zinc-600">{{ $value }}</p>
                                @endif
                            @endforeach
                        @else
                            <p class="text-zinc-500">No shipping address.</p>
                        @endif
                    </div>

                    <div>
                        <p class="font-medium text-zinc-800">Billing</p>
                        @if (is_array($order->billing_address_json))
                            @foreach ($order->billing_address_json as $value)
                                @if (! empty($value))
                                    <p class="text-zinc-600">{{ $value }}</p>
                                @endif
                            @endforeach
                        @else
                            <p class="text-zinc-500">No billing address.</p>
                        @endif
                    </div>
                </div>
            </section>

            <section class="rounded-lg border border-zinc-200 bg-white p-5">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-700">Payments</h2>

                <div class="mt-3 space-y-2 text-sm">
                    @forelse ($order->payments as $payment)
                        <div class="rounded-md border border-zinc-200 px-3 py-2">
                            <p class="font-medium text-zinc-900">{{ $payment->method->value }} · {{ $payment->status->value }}</p>
                            <p class="text-xs text-zinc-500">{{ strtoupper($payment->currency) }} {{ number_format($payment->amount / 100, 2) }}</p>
                            <p class="text-xs text-zinc-500">{{ $payment->provider_payment_id ?: 'no provider id' }}</p>
                        </div>
                    @empty
                        <p class="text-zinc-500">No payment records.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
@endsection
