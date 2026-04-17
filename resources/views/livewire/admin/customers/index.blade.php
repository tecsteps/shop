<div class="space-y-4">
    <flux:heading size="xl">Customers</flux:heading>

    <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by name or email..." icon="magnifying-glass" class="sm:max-w-xs" />

    <div class="overflow-x-auto rounded-lg border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
        <table class="w-full text-sm">
            <thead class="text-left text-xs uppercase text-neutral-500">
                <tr>
                    <th class="px-4 py-2">Name</th>
                    <th class="px-4 py-2">Email</th>
                    <th class="px-4 py-2">Orders</th>
                    <th class="px-4 py-2">Spent</th>
                    <th class="px-4 py-2">Joined</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($customers as $customer)
                    <tr wire:key="customer-{{ $customer->id }}" class="border-t border-neutral-100 dark:border-neutral-800">
                        <td class="px-4 py-2">
                            <a href="{{ url('/admin/customers/'.$customer->id) }}" class="font-medium hover:underline">{{ $customer->name ?? '-' }}</a>
                        </td>
                        <td class="px-4 py-2">{{ $customer->email }}</td>
                        <td class="px-4 py-2">{{ $customer->orders_count }}</td>
                        <td class="px-4 py-2">{{ number_format(((int) ($customer->total_spent ?? 0)) / 100, 2) }}</td>
                        <td class="px-4 py-2 text-neutral-500">{{ $customer->created_at?->format('Y-m-d') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-neutral-500">No customers yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $customers->links() }}</div>
</div>
