<div class="space-y-6">
    <div>
        <flux:heading size="xl">Customers</flux:heading>
        <flux:text>Customer profiles, order counts, and marketing status.</flux:text>
    </div>

    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <div class="border-b border-zinc-200 p-4 dark:border-zinc-800">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search customers" icon="magnifying-glass" />
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                <thead class="bg-zinc-50 text-left text-xs uppercase tracking-wide text-zinc-500 dark:bg-zinc-950">
                    <tr>
                        <th class="px-5 py-3">Customer</th>
                        <th class="px-5 py-3">Marketing</th>
                        <th class="px-5 py-3">Orders</th>
                        <th class="px-5 py-3">Last update</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($customers as $customer)
                        <tr wire:key="admin-customer-{{ $customer->id }}">
                            <td class="px-5 py-4">
                                <a href="{{ route('admin.customers.show', $customer) }}" wire:navigate class="font-medium hover:underline">{{ $customer->name ?? 'Guest customer' }}</a>
                                <div class="text-xs text-zinc-500">{{ $customer->email }}</div>
                            </td>
                            <td class="px-5 py-4"><flux:badge>{{ $customer->marketing_opt_in ? 'opted in' : 'not opted in' }}</flux:badge></td>
                            <td class="px-5 py-4">{{ $customer->orders_count }}</td>
                            <td class="px-5 py-4">{{ $customer->updated_at?->format('M j, Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-16 text-center text-sm text-zinc-500">No customers match the current filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 p-4 dark:border-zinc-800">
            {{ $customers->links() }}
        </div>
    </div>
</div>
