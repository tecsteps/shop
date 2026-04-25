<x-admin.layout :title="$customer->name">
    <h1 class="text-3xl font-bold tracking-normal">{{ $customer->name }}</h1>
    <p class="text-zinc-600 dark:text-zinc-400">{{ $customer->email }}</p>
    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <section class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="font-semibold">Order history</h2>
            <div class="mt-4 grid gap-2">
                @foreach($customer->orders as $order)
                    <a class="underline" href="{{ route('admin.orders.show', $order) }}">#{{ $order->order_number }} - <x-shop.price :amount="$order->total_amount" :currency="$order->currency" /></a>
                @endforeach
            </div>
        </section>
        <section class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="font-semibold">Addresses</h2>
            <div class="mt-4 grid gap-2">
                @foreach($customer->addresses as $address)
                    <p>{{ $address->address1 }}, {{ $address->postal_code }} {{ $address->city }}, {{ $address->country_code }}</p>
                @endforeach
            </div>
        </section>
    </div>
</x-admin.layout>

