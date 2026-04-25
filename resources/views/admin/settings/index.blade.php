<x-admin.layout :title="'Settings'">
    <h1 class="text-3xl font-bold tracking-normal">Settings</h1>
    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <form method="POST" action="{{ route('admin.settings.update') }}" class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            @csrf @method('PATCH')
            <h2 class="font-semibold">General</h2>
            <label class="mt-4 grid gap-2"><span>Store name</span><input name="name" value="{{ $store->name }}" class="rounded-md border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950"></label>
            <button class="mt-4 rounded-md bg-zinc-950 px-4 py-2 text-white dark:bg-white dark:text-zinc-950">Save settings</button>
        </form>
        <section class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="font-semibold">Domains</h2>
            <div class="mt-4 grid gap-2">
                @foreach($store->domains as $domain)
                    <p>{{ $domain->hostname }} - {{ $domain->type->value }}</p>
                @endforeach
            </div>
        </section>
        <section class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="font-semibold">Shipping zones</h2>
            @foreach($zones as $zone)
                <div class="mt-4 rounded-md border border-zinc-200 p-3 dark:border-zinc-800">
                    <h3 class="font-medium">{{ $zone->name }}</h3>
                    @foreach($zone->rates as $rate)
                        <p class="text-sm">{{ $rate->name }} - <x-shop.price :amount="$rate->price_amount" /></p>
                    @endforeach
                </div>
            @endforeach
            <form method="POST" action="{{ route('admin.settings.shipping-rates.store') }}" class="mt-4 grid gap-3">
                @csrf
                <select name="shipping_zone_id" class="rounded-md border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">@foreach($zones as $zone)<option value="{{ $zone->id }}">{{ $zone->name }}</option>@endforeach</select>
                <input name="name" placeholder="Rate name" class="rounded-md border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">
                <input name="price_amount" type="number" step="0.01" placeholder="Price" class="rounded-md border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">
                <button class="rounded-md border border-zinc-300 px-4 py-2 dark:border-zinc-700">Add shipping rate</button>
            </form>
        </section>
        <section class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="font-semibold">Taxes</h2>
            <p class="mt-2">Prices include tax: {{ $tax->prices_include_tax ? 'Yes' : 'No' }}</p>
            <form method="POST" action="{{ route('admin.settings.taxes.toggle') }}" class="mt-4">@csrf @method('PATCH')<button class="rounded-md border border-zinc-300 px-4 py-2 dark:border-zinc-700">Toggle tax inclusion</button></form>
        </section>
    </div>
</x-admin.layout>

