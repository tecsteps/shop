<div>
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">Customers</flux:heading>
    </div>

    {{-- Search --}}
    <div class="mb-6">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by name or email..." icon="magnifying-glass" />
    </div>

    {{-- Customers table --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                        <th class="text-left px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Name</th>
                        <th class="text-left px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Email</th>
                        <th class="text-center px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Orders</th>
                        <th class="text-right px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Total Spent</th>
                        <th class="text-right px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Created</th>
                    </tr>
                </thead>
                <tbody wire:loading.class="opacity-50" class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($this->customers as $customer)
                        <tr wire:key="customer-{{ $customer->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.customers.show', $customer) }}" wire:navigate class="font-medium text-zinc-900 dark:text-white hover:underline">
                                    {{ $customer->name ?? '-' }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                {{ $customer->email }}
                            </td>
                            <td class="px-4 py-3 text-center text-zinc-900 dark:text-white">
                                {{ $customer->orders_count }}
                            </td>
                            <td class="px-4 py-3 text-right font-medium text-zinc-900 dark:text-white">
                                {{ number_format(($customer->orders_sum_total_amount ?? 0) / 100, 2) }} EUR
                            </td>
                            <td class="px-4 py-3 text-right text-zinc-500 dark:text-zinc-400">
                                {{ $customer->created_at->format('M j, Y') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center text-zinc-500 dark:text-zinc-400">
                                No customers found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($this->customers->hasPages())
            <div class="px-4 py-3 border-t border-zinc-200 dark:border-zinc-700">
                {{ $this->customers->links() }}
            </div>
        @endif
    </div>
</div>
