<x-admin.layout :title="'Order '.$order->order_number">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div><h1 class="text-3xl font-bold tracking-normal">Order #{{ $order->order_number }}</h1><p class="text-zinc-600 dark:text-zinc-400">{{ $order->email }} - {{ $order->financial_status }} / {{ $order->fulfillment_status }}</p></div>
        <div class="flex flex-wrap gap-2">
            @if($order->payments->first()?->method === 'bank_transfer' && $order->financial_status !== 'paid')
                <form method="POST" action="{{ route('admin.orders.confirm-payment', $order) }}">@csrf<button class="rounded-md bg-zinc-950 px-3 py-2 text-sm text-white dark:bg-white dark:text-zinc-950">Confirm bank transfer</button></form>
            @endif
            <form method="POST" action="{{ route('admin.orders.fulfillments', $order) }}">@csrf<button class="rounded-md border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700">Create fulfillment</button></form>
        </div>
    </div>
    <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_360px]">
        <section class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="font-semibold">Line items</h2>
            <div class="mt-4 grid gap-3">
                @foreach($order->lines as $line)
                    <div class="flex justify-between"><span>{{ $line->quantity }} x {{ $line->title }}</span><span><x-shop.price :amount="$line->total_amount" :currency="$order->currency" /></span></div>
                @endforeach
            </div>
            <h2 class="mt-8 font-semibold">Timeline</h2>
            <div class="mt-4 grid gap-2 text-sm">
                @foreach($order->timeline_json as $event)
                    <div class="rounded-md bg-zinc-100 p-3 dark:bg-zinc-800">{{ $event['message'] }} <span class="text-zinc-500">{{ $event['at'] }}</span></div>
                @endforeach
            </div>
            <h2 class="mt-8 font-semibold">Fulfillments</h2>
            <div class="mt-4 grid gap-2">
                @forelse($order->fulfillments as $fulfillment)
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-md border border-zinc-200 p-3 dark:border-zinc-800">
                        <span>{{ $fulfillment->status }}</span>
                        <div class="flex gap-2">
                            <form method="POST" action="{{ route('admin.fulfillments.ship', $fulfillment) }}">@csrf @method('PATCH')<button class="underline">Mark shipped</button></form>
                            <form method="POST" action="{{ route('admin.fulfillments.deliver', $fulfillment) }}">@csrf @method('PATCH')<button class="underline">Mark delivered</button></form>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">No fulfillments yet.</p>
                @endforelse
            </div>
        </section>
        <aside class="grid h-max gap-4">
            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="font-semibold">Totals</h2>
                <dl class="mt-4 grid gap-2 text-sm">
                    <div class="flex justify-between"><dt>Subtotal</dt><dd><x-shop.price :amount="$order->subtotal_amount" :currency="$order->currency" /></dd></div>
                    <div class="flex justify-between"><dt>Shipping</dt><dd><x-shop.price :amount="$order->shipping_amount" :currency="$order->currency" /></dd></div>
                    <div class="flex justify-between"><dt>Tax</dt><dd><x-shop.price :amount="$order->tax_amount" :currency="$order->currency" /></dd></div>
                    <div class="flex justify-between font-semibold"><dt>Total</dt><dd><x-shop.price :amount="$order->total_amount" :currency="$order->currency" /></dd></div>
                </dl>
            </div>
            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="font-semibold">Customer</h2>
                <p class="mt-2">{{ $order->customer?->name }}</p>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $order->email }}</p>
            </div>
            <form method="POST" action="{{ route('admin.orders.refunds', $order) }}" class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                @csrf
                <h2 class="font-semibold">Refund</h2>
                <label class="mt-4 grid gap-2"><span>Amount</span><input name="amount" type="number" step="0.01" value="5.00" class="rounded-md border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950"></label>
                <button class="mt-3 rounded-md border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700">Process refund</button>
            </form>
        </aside>
    </div>
</x-admin.layout>

