<x-storefront.layout :title="'Order '.$order->order_number">
    <section class="mx-auto max-w-5xl px-4 py-10">
        <h1 class="text-3xl font-bold tracking-normal">Order #{{ $order->order_number }}</h1>
        <p class="mt-2 text-zinc-600 dark:text-zinc-400">{{ $order->financial_status }} / {{ $order->fulfillment_status }}</p>
        <div class="mt-6 grid gap-3">
            @foreach($order->lines as $line)
                <div class="flex justify-between rounded-md border border-zinc-200 p-4 dark:border-zinc-800">
                    <span>{{ $line->quantity }} x {{ $line->title }}</span>
                    <span><x-shop.price :amount="$line->total_amount" :currency="$order->currency" /></span>
                </div>
            @endforeach
        </div>
        <p class="mt-6 text-xl font-semibold">Total: <x-shop.price :amount="$order->total_amount" :currency="$order->currency" /></p>
    </section>
</x-storefront.layout>

