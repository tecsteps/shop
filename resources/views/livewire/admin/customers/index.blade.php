<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl">Customers</flux:heading>
    </div>

    @if (! $hasCustomers)
        <div class="flex flex-col items-center rounded-lg border border-zinc-200 bg-white px-6 py-16 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:icon name="users" class="size-12 text-zinc-300 dark:text-zinc-600" />
            <flux:heading size="lg" class="mt-4">No customers yet</flux:heading>
            <flux:text class="mt-1">Customers who register or check out will appear here.</flux:text>
        </div>
    @else
        <flux:input icon="magnifying-glass" wire:model.live.debounce.300ms="search" placeholder="Search by name or email..." class="w-full sm:w-72" aria-label="Search customers" />

        <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <table class="w-full min-w-[720px] text-left text-sm" wire:loading.class="opacity-50">
                <thead>
                    <tr class="border-b border-zinc-200 text-xs tracking-wider text-zinc-500 uppercase dark:border-zinc-700 dark:text-zinc-400">
                        <th class="px-4 py-3 font-medium">Name</th>
                        <th class="px-4 py-3 font-medium">Email</th>
                        <th class="px-4 py-3 font-medium">Orders</th>
                        <th class="px-4 py-3 font-medium">Total spent</th>
                        <th class="px-4 py-3 font-medium">Marketing</th>
                        <th class="px-4 py-3 font-medium">Created</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($customers as $customer)
                        <tr wire:key="customer-{{ $customer->id }}">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.customers.show', $customer) }}" wire:navigate class="font-medium text-zinc-900 hover:underline dark:text-zinc-100">
                                    {{ $customer->name ?? '—' }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $customer->email }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $customer->orders_count }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ \App\Support\Money::format((int) $customer->orders_sum_total_amount, $currency) }}</td>
                            <td class="px-4 py-3">
                                <flux:badge size="sm" :color="$customer->marketing_opt_in ? 'green' : 'zinc'">
                                    {{ $customer->marketing_opt_in ? 'Opted in' : 'Opted out' }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $customer->created_at?->format('M j, Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                No customers match your search.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $customers->links() }}
    @endif
</div>
