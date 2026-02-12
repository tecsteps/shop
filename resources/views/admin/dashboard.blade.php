@extends('admin.layout')

@section('title', 'Dashboard')

@section('content')
    <div class="mb-6 flex items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900">Dashboard</h1>
            <p class="mt-1 text-sm text-zinc-600">Core KPIs for the current store.</p>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-lg border border-zinc-200 bg-white p-4">
            <p class="text-sm text-zinc-600">Sales</p>
            <p class="mt-2 text-2xl font-semibold">{{ strtoupper($currency) }} {{ number_format($totalSales / 100, 2) }}</p>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-4">
            <p class="text-sm text-zinc-600">Orders</p>
            <p class="mt-2 text-2xl font-semibold">{{ number_format($ordersCount) }}</p>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-4">
            <p class="text-sm text-zinc-600">Customers</p>
            <p class="mt-2 text-2xl font-semibold">{{ number_format($customersCount) }}</p>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-4">
            <p class="text-sm text-zinc-600">Products</p>
            <p class="mt-2 text-2xl font-semibold">{{ number_format($productsCount) }}</p>
            <p class="mt-2 text-xs text-zinc-500">Low stock variants: {{ number_format($lowStockCount) }}</p>
        </div>
    </div>

    <div class="mt-8 rounded-lg border border-zinc-200 bg-white">
        <div class="border-b border-zinc-200 px-4 py-3">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-700">Recent Orders</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm">
                <thead class="bg-zinc-50 text-left text-xs uppercase tracking-wide text-zinc-500">
                    <tr>
                        <th class="px-4 py-3">Order</th>
                        <th class="px-4 py-3">Customer</th>
                        <th class="px-4 py-3">Financial</th>
                        <th class="px-4 py-3">Fulfillment</th>
                        <th class="px-4 py-3 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 bg-white">
                    @forelse ($recentOrders as $order)
                        <tr>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.orders.show', $order) }}" class="font-medium text-zinc-900 hover:underline">
                                    {{ $order->order_number }}
                                </a>
                                <div class="text-xs text-zinc-500">{{ optional($order->placed_at)->format('M j, Y g:i A') ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3">
                                {{ $order->customer?->name ?? 'Guest' }}
                                <div class="text-xs text-zinc-500">{{ $order->email }}</div>
                            </td>
                            <td class="px-4 py-3">{{ $order->financial_status->value }}</td>
                            <td class="px-4 py-3">{{ $order->fulfillment_status->value }}</td>
                            <td class="px-4 py-3 text-right font-medium">{{ strtoupper($order->currency) }} {{ number_format($order->total_amount / 100, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-zinc-500">No orders yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
