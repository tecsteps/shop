<x-storefront.layout :title="'Orders'">
    <section class="mx-auto max-w-5xl px-4 py-10">
        <h1 class="text-3xl font-bold tracking-normal">Orders</h1>
        <div class="mt-6 grid gap-3">
            @foreach($orders as $order)
                <a href="{{ route('account.orders.show', $order->order_number) }}" class="flex justify-between rounded-md border border-zinc-200 p-4 dark:border-zinc-800">
                    <span>#{{ $order->order_number }} - {{ $order->financial_status }}</span>
                    <span><x-shop.price :amount="$order->total_amount" :currency="$order->currency" /></span>
                </a>
            @endforeach
        </div>
    </section>
</x-storefront.layout>

