<section class="space-y-6">
    <div>
        <flux:heading size="xl">Customers</flux:heading>
        <flux:text class="mt-1">Search customer accounts and review order value.</flux:text>
    </div>

    <div class="max-w-xl">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Search name or email..." aria-label="Search customers" />
    </div>

    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Orders</th>
                        <th class="px-4 py-3 text-right">Total spent</th>
                        <th class="px-4 py-3">Created</th>
                    </tr>
                </thead>
                <tbody wire:loading.class="opacity-50" class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($customers as $customer)
                        <tr wire:key="admin-customer-{{ $customer->getKey() }}">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.customers.show', $customer) }}" class="font-medium text-zinc-950 hover:underline dark:text-white" wire:navigate>
                                    {{ $customer->name ?: 'Unnamed customer' }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $customer->email }}</td>
                            <td class="px-4 py-3">{{ $customer->orders_count }}</td>
                            <td class="px-4 py-3 text-right">
                                <x-storefront.price :amount="(int) ($customer->total_spent ?? 0)" :currency="$storeCurrency" class="justify-end" />
                            </td>
                            <td class="px-4 py-3 text-zinc-500">{{ $customer->created_at?->format('M j, Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-16 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center gap-3">
                                    <div class="flex size-12 items-center justify-center rounded-lg bg-zinc-100 text-zinc-400 dark:bg-zinc-800">
                                        <flux:icon name="users" class="size-6" />
                                    </div>
                                    <flux:heading size="lg">No customers found</flux:heading>
                                    <flux:text>Customer accounts will appear here after registration or checkout.</flux:text>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $customers->links() }}
</section>
