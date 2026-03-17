<div>
    <div class="mb-6">
        <flux:heading size="xl">Customers</flux:heading>
    </div>

    <div class="mb-4">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by name or email..." icon="magnifying-glass" />
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <table class="w-full text-left text-sm" wire:loading.class="opacity-50" wire:target="search">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700">
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Name</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Email</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Orders</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Total spent</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Created</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($customers as $customer)
                    <tr class="border-b border-gray-100 dark:border-gray-800" wire:key="cust-{{ $customer->id }}">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.customers.show', $customer) }}" wire:navigate class="font-medium text-gray-900 hover:text-blue-600 dark:text-white">
                                {{ $customer->first_name }} {{ $customer->last_name }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ $customer->email }}</td>
                        <td class="px-4 py-3">{{ $customer->orders_count }}</td>
                        <td class="px-4 py-3">${{ number_format(($customer->orders_sum_total_amount ?? 0) / 100, 2) }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $customer->created_at->format('M j, Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-12 text-center text-gray-500">No customers found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $customers->links() }}</div>
</div>
