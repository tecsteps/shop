<div class="space-y-6">
    <flux:heading size="xl">Customers</flux:heading>

    <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by name or email..." />

    <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                    <th class="px-4 py-3 font-medium">Name</th>
                    <th class="px-4 py-3 font-medium">Email</th>
                    <th class="px-4 py-3 font-medium">Orders</th>
                    <th class="px-4 py-3 font-medium">Joined</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($customers as $customer)
                    <tr wire:key="customer-{{ $customer->id }}" class="border-b border-zinc-100 dark:border-zinc-800">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.customers.show', $customer) }}" class="text-blue-600 hover:underline dark:text-blue-400">{{ $customer->name }}</a>
                        </td>
                        <td class="px-4 py-3">{{ $customer->email }}</td>
                        <td class="px-4 py-3">{{ $customer->orders_count ?? $customer->orders()->count() }}</td>
                        <td class="px-4 py-3">{{ $customer->created_at->format('M d, Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div>{{ $customers->links() }}</div>
</div>
