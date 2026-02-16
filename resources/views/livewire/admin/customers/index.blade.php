<div>
    <div class="mb-6 flex items-center justify-between">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search customers..." icon="magnifying-glass" class="w-64" />
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
            <thead class="bg-gray-50 dark:bg-zinc-800/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Orders</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                @forelse($customers as $customer)
                    <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800/50">
                        <td class="px-6 py-4 text-sm font-medium"><a href="{{ route('admin.customers.show', $customer) }}" class="text-gray-900 hover:underline dark:text-white">{{ $customer->first_name }} {{ $customer->last_name }}</a></td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $customer->email }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $customer->orders_count }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No customers found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $customers->links() }}</div>
</div>
