<x-storefront.layout :title="'Addresses'">
    <section class="mx-auto grid max-w-5xl gap-8 px-4 py-10 md:grid-cols-[1fr_360px]">
        <div>
            <h1 class="text-3xl font-bold tracking-normal">Addresses</h1>
            <div class="mt-6 grid gap-3">
                @foreach($addresses as $address)
                    <div class="rounded-md border border-zinc-200 p-4 dark:border-zinc-800">
                        <h2 class="font-semibold">{{ $address->name }}</h2>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $address->address1 }}, {{ $address->postal_code }} {{ $address->city }}, {{ $address->country_code }}</p>
                    </div>
                @endforeach
            </div>
        </div>
        <form method="POST" action="{{ route('account.addresses.save') }}" class="h-max rounded-lg border border-zinc-200 p-5 dark:border-zinc-800">
            @csrf
            <h2 class="font-semibold">Add address</h2>
            <div class="mt-4 grid gap-3">
                <label class="grid gap-1"><span>Name</span><input name="name" class="rounded-md border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900"></label>
                <label class="grid gap-1"><span>Address</span><input name="address1" class="rounded-md border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900"></label>
                <label class="grid gap-1"><span>City</span><input name="city" class="rounded-md border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900"></label>
                <label class="grid gap-1"><span>Postal code</span><input name="postal_code" class="rounded-md border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900"></label>
                <label class="grid gap-1"><span>Country code</span><input name="country_code" value="DE" maxlength="2" class="rounded-md border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900"></label>
                <button class="rounded-md bg-zinc-950 px-4 py-2 text-white dark:bg-white dark:text-zinc-950">Save address</button>
            </div>
        </form>
    </section>
</x-storefront.layout>

