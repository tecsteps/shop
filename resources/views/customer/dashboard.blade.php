@extends('storefront.layout')

@section('content')
<section class="space-y-6">
    <header>
        <h1 class="text-3xl font-bold tracking-tight text-zinc-900">Welcome back, {{ $currentCustomer->name ?: 'Customer' }}!</h1>
        <p class="mt-2 text-sm text-zinc-600">Manage your account, addresses, and recent orders.</p>
    </header>

    <div class="grid gap-4 sm:grid-cols-3">
        <a href="{{ route('storefront.account.orders.index') }}" class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <h2 class="text-base font-semibold text-zinc-900">Orders</h2>
            <p class="mt-2 text-sm text-zinc-600">View your order history and statuses.</p>
        </a>

        <a href="{{ route('storefront.account.addresses.index') }}" class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <h2 class="text-base font-semibold text-zinc-900">Addresses</h2>
            <p class="mt-2 text-sm text-zinc-600">Add, edit, and choose your default address.</p>
        </a>

        <form method="POST" action="{{ route('storefront.account.logout') }}" class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            @csrf
            <h2 class="text-base font-semibold text-zinc-900">Log out</h2>
            <p class="mt-2 text-sm text-zinc-600">End your account session on this device.</p>
            <button type="submit" class="mt-4 rounded-md border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100">Log out</button>
        </form>
    </div>

    <section class="rounded-xl border border-zinc-200 bg-white p-5">
        <div class="mb-4 flex items-center justify-between gap-3">
            <h2 class="text-xl font-semibold text-zinc-900">Recent Orders</h2>
            <a href="{{ route('storefront.account.orders.index') }}" class="text-sm font-medium text-zinc-600 hover:text-zinc-900">View all</a>
        </div>

        @if ($recentOrders->isEmpty())
            <p class="text-sm text-zinc-600">You have not placed any orders yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 text-sm">
                    <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                    <tr>
                        <th class="px-3 py-2">Order</th>
                        <th class="px-3 py-2">Date</th>
                        <th class="px-3 py-2">Status</th>
                        <th class="px-3 py-2">Total</th>
                        <th class="px-3 py-2">Action</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200">
                    @foreach ($recentOrders as $order)
                        <tr>
                            <td class="px-3 py-3 font-medium text-zinc-900">{{ $order->order_number }}</td>
                            <td class="px-3 py-3 text-zinc-600">{{ optional($order->placed_at)->format('M d, Y') ?: '-' }}</td>
                            <td class="px-3 py-3 text-zinc-600">{{ ucfirst($order->financial_status->value) }}</td>
                            <td class="px-3 py-3 font-medium text-zinc-900">{{ number_format($order->total_amount / 100, 2, '.', ',') }} {{ $order->currency }}</td>
                            <td class="px-3 py-3">
                                <a href="{{ route('storefront.account.orders.show', ['orderNumber' => $order->order_number]) }}" class="text-sm font-medium text-zinc-700 hover:underline">View</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</section>
@endsection
