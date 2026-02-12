@extends('storefront.layout')

@section('content')
<section class="space-y-6">
    <header class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-zinc-900">Order History</h1>
            <p class="mt-2 text-sm text-zinc-600">Review all previous purchases and order statuses.</p>
        </div>
        <a href="{{ route('storefront.account.dashboard') }}" class="rounded-md border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100">Back to account</a>
    </header>

    <section class="rounded-xl border border-zinc-200 bg-white p-5">
        @if ($orders->isEmpty())
            <div class="py-10 text-center">
                <h2 class="text-lg font-semibold text-zinc-900">No orders yet</h2>
                <p class="mt-2 text-sm text-zinc-600">Your orders will appear here once you complete checkout.</p>
                <a href="{{ route('home') }}" class="mt-6 inline-flex rounded-md bg-zinc-900 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-700">Start shopping</a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 text-sm">
                    <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                    <tr>
                        <th class="px-3 py-2">Order</th>
                        <th class="px-3 py-2">Placed</th>
                        <th class="px-3 py-2">Payment</th>
                        <th class="px-3 py-2">Status</th>
                        <th class="px-3 py-2">Total</th>
                        <th class="px-3 py-2">Action</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200">
                    @foreach ($orders as $order)
                        <tr>
                            <td class="px-3 py-3 font-medium text-zinc-900">{{ $order->order_number }}</td>
                            <td class="px-3 py-3 text-zinc-600">{{ optional($order->placed_at)->format('M d, Y') ?: '-' }}</td>
                            <td class="px-3 py-3 text-zinc-600">{{ ucfirst(str_replace('_', ' ', $order->payment_method->value)) }}</td>
                            <td class="px-3 py-3 text-zinc-600">{{ ucfirst($order->financial_status->value) }}</td>
                            <td class="px-3 py-3 font-medium text-zinc-900">{{ number_format($order->total_amount / 100, 2, '.', ',') }} {{ $order->currency }}</td>
                            <td class="px-3 py-3">
                                <a href="{{ route('storefront.account.orders.show', ['orderNumber' => $order->order_number]) }}" class="font-medium text-zinc-700 hover:underline">View</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $orders->links() }}
            </div>
        @endif
    </section>
</section>
@endsection
