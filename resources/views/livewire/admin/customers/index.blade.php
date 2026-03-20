<div>
    <flux:heading size="xl">Customers</flux:heading>
    <div class="mt-6">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search customers..." icon="magnifying-glass" />
    </div>
    <div class="mt-4 overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Email</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Orders</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Joined</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                @forelse($customers as $customer)
                    <tr wire:key="customer-{{ $customer->id }}">
                        <td class="px-4 py-3 text-sm"><a href="{{ route('admin.customers.show', $customer) }}" class="font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400">{{ $customer->name }}</a></td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $customer->email }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $customer->orders_count }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $customer->created_at?->format('M d, Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">No customers found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $customers->links() }}</div>
</div>
