<x-admin.layout :title="'Orders'">
    <h1 class="text-3xl font-bold tracking-normal">Orders</h1>
    <form method="GET" class="mt-6 flex gap-3">
        <select name="status" class="rounded-md border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900"><option value="">All statuses</option><option value="paid">Paid</option><option value="pending">Pending</option><option value="refunded">Refunded</option></select>
        <button class="rounded-md border border-zinc-300 px-3 py-2 dark:border-zinc-700">Filter</button>
    </form>
    <div class="mt-6 grid gap-3">
        @foreach($orders as $order)
            <a href="{{ route('admin.orders.show', $order) }}" class="flex flex-wrap justify-between gap-3 rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <span>#{{ $order->order_number }} - {{ $order->email }}</span>
                <span>{{ $order->financial_status }} / <x-shop.price :amount="$order->total_amount" :currency="$order->currency" /></span>
            </a>
        @endforeach
    </div>
</x-admin.layout>

