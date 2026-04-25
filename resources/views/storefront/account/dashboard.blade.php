<x-storefront.layout :title="'Account'">
    <section class="mx-auto max-w-5xl px-4 py-10">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold tracking-normal">Account</h1>
                <p class="text-zinc-600 dark:text-zinc-400">{{ auth('customer')->user()->email }}</p>
            </div>
            <form method="POST" action="{{ route('account.logout') }}">@csrf<button class="rounded-md border border-zinc-300 px-3 py-2 dark:border-zinc-700">Log out</button></form>
        </div>
        <div class="mt-8 grid gap-4 md:grid-cols-3">
            <a class="rounded-lg border border-zinc-200 p-5 dark:border-zinc-800" href="{{ route('account.orders') }}">Orders</a>
            <a class="rounded-lg border border-zinc-200 p-5 dark:border-zinc-800" href="{{ route('account.addresses') }}">Addresses</a>
            <a class="rounded-lg border border-zinc-200 p-5 dark:border-zinc-800" href="{{ route('cart.show') }}">Cart</a>
        </div>
        <h2 class="mt-10 text-xl font-semibold">Recent orders</h2>
        <div class="mt-4 grid gap-3">
            @foreach($orders as $order)
                <a href="{{ route('account.orders.show', $order->order_number) }}" class="flex justify-between rounded-md border border-zinc-200 p-4 dark:border-zinc-800">
                    <span>#{{ $order->order_number }}</span>
                    <span><x-shop.price :amount="$order->total_amount" :currency="$order->currency" /></span>
                </a>
            @endforeach
        </div>
    </section>
</x-storefront.layout>

