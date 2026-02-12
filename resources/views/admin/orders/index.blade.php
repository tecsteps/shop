@extends('admin.layout')

@section('title', 'Orders')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-zinc-900">Orders</h1>
        <p class="mt-1 text-sm text-zinc-600">Browse and manage customer orders.</p>
    </div>

    <form method="GET" class="mb-4 grid gap-3 rounded-lg border border-zinc-200 bg-white p-4 md:grid-cols-[1fr_220px_auto]">
        <input
            type="text"
            name="search"
            value="{{ $search }}"
            placeholder="Search by order number or email..."
            class="w-full rounded-md border border-zinc-300 px-3 py-2 text-sm"
        >

        <select name="status" class="w-full rounded-md border border-zinc-300 px-3 py-2 text-sm">
            <option value="">All statuses</option>
            @foreach ($statuses as $statusOption)
                <option value="{{ $statusOption->value }}" @selected($status === $statusOption->value)>
                    {{ ucfirst($statusOption->value) }}
                </option>
            @endforeach
        </select>

        <button type="submit" class="rounded-md border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100">
            Filter
        </button>
    </form>

    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm">
                <thead class="bg-zinc-50 text-left text-xs uppercase tracking-wide text-zinc-500">
                    <tr>
                        <th class="px-4 py-3">Order</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Customer</th>
                        <th class="px-4 py-3">Financial</th>
                        <th class="px-4 py-3">Fulfillment</th>
                        <th class="px-4 py-3 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 bg-white">
                    @forelse ($orders as $order)
                        <tr>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.orders.show', $order) }}" class="font-medium text-zinc-900 hover:underline">
                                    {{ $order->order_number }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-zinc-600">{{ optional($order->placed_at)->format('M j, Y g:i A') ?? '-' }}</td>
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
                            <td colspan="6" class="px-4 py-10 text-center text-zinc-500">No orders found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 px-4 py-3">
            {{ $orders->links() }}
        </div>
    </div>
@endsection
