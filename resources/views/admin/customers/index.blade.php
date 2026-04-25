<x-admin.layout :title="'Customers'">
    <h1 class="text-3xl font-bold tracking-normal">Customers</h1>
    <div class="mt-6 grid gap-3">
        @foreach($customers as $customer)
            <a href="{{ route('admin.customers.show', $customer) }}" class="flex justify-between rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <span>{{ $customer->name }} - {{ $customer->email }}</span>
                <span>{{ $customer->orders_count }} orders</span>
            </a>
        @endforeach
    </div>
</x-admin.layout>

