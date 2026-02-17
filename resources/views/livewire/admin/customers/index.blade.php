<div>
    <div class="mb-6">
        <flux:heading size="xl">Customers</flux:heading>
    </div>

    <div class="mb-4">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by name or email..." icon="magnifying-glass" />
    </div>

    <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        @if ($this->customers->isEmpty())
            <div class="py-12 text-center">
                <flux:text class="text-zinc-400">No customers found.</flux:text>
            </div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                        <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Name</th>
                        <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Email</th>
                        <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Orders</th>
                        <th class="px-4 py-3 text-right font-medium text-zinc-500 dark:text-zinc-400">Total spent</th>
                        <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Created</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->customers as $customer)
                        <tr class="border-b border-zinc-100 dark:border-zinc-700/50">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.customers.show', $customer) }}" class="font-medium text-zinc-900 hover:text-blue-600 dark:text-zinc-100 dark:hover:text-blue-400" wire:navigate>{{ $customer->name }}</a>
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $customer->email }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $customer->orders_count }}</td>
                            <td class="px-4 py-3 text-right text-zinc-900 dark:text-zinc-100">${{ number_format(($customer->orders_sum_total_amount ?? 0) / 100, 2) }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $customer->created_at->format('M j, Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="px-4 py-3">{{ $this->customers->links() }}</div>
        @endif
    </div>
</div>
