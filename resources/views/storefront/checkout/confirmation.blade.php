<x-storefront.layout :title="'Order '.$order->order_number">
    <section class="mx-auto max-w-3xl px-4 py-10">
        <div class="rounded-lg border border-zinc-200 p-6 dark:border-zinc-800">
            <h1 class="text-3xl font-bold tracking-normal">Order confirmed</h1>
            <p class="mt-2 text-zinc-600 dark:text-zinc-400">Order #{{ $order->order_number }} has been created.</p>
            <dl class="mt-6 grid gap-2 text-sm">
                <div class="flex justify-between"><dt>Status</dt><dd>{{ $order->financial_status }}</dd></div>
                <div class="flex justify-between"><dt>Total</dt><dd><x-shop.price :amount="$order->total_amount" :currency="$order->currency" /></dd></div>
                <div class="flex justify-between"><dt>Payment</dt><dd>{{ $order->payments->first()?->method }}</dd></div>
            </dl>
            @if($order->payments->first()?->method === 'bank_transfer')
                <div class="mt-6 rounded-md bg-zinc-100 p-4 text-sm dark:bg-zinc-900">
                    <h2 class="font-semibold">Bank transfer instructions</h2>
                    <p>IBAN: DE89370400440532013000</p>
                    <p>BIC: COBADEFFXXX</p>
                    <p>Reference: {{ $order->payments->first()?->reference }}</p>
                </div>
            @endif
            <a class="mt-6 inline-flex rounded-md bg-zinc-950 px-4 py-2 text-white dark:bg-white dark:text-zinc-950" href="{{ route('home') }}">Continue shopping</a>
        </div>
    </section>
</x-storefront.layout>

