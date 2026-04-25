<x-admin.layout :title="'Dashboard'">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-normal">Dashboard</h1>
            <p class="text-zinc-600 dark:text-zinc-400">Sales, orders, customers, and catalog status.</p>
        </div>
        <select class="rounded-md border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900"><option>Last 30 days</option><option>Today</option></select>
    </div>
    <div class="mt-6 grid gap-4 md:grid-cols-4">
        <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900"><p class="text-sm text-zinc-500">Total sales</p><p class="mt-2 text-2xl font-bold"><x-shop.price :amount="$sales" /></p></div>
        <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900"><p class="text-sm text-zinc-500">Orders</p><p class="mt-2 text-2xl font-bold">{{ $orders->count() }}</p></div>
        <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900"><p class="text-sm text-zinc-500">Customers</p><p class="mt-2 text-2xl font-bold">{{ $customers }}</p></div>
        <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900"><p class="text-sm text-zinc-500">Products</p><p class="mt-2 text-2xl font-bold">{{ $products }}</p></div>
    </div>
    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <section class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="font-semibold">Orders over time</h2>
            <div class="mt-4 flex h-48 items-end gap-2">
                @foreach(range(1, 12) as $bar)
                    <div class="w-full rounded-t bg-zinc-900 dark:bg-zinc-100" style="height: {{ 20 + ($bar * 6) }}%"></div>
                @endforeach
            </div>
        </section>
        <section class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="font-semibold">Conversion funnel</h2>
            <div class="mt-4 grid gap-3 text-sm">
                <div>Visits - 2400</div><div>Add to cart - 410</div><div>Checkout started - 180</div><div>Checkout completed - 64</div>
            </div>
        </section>
    </div>
</x-admin.layout>

