<div>
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" wire:navigate>Home</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Customers</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:heading size="xl" class="mb-6">Customers</flux:heading>

    <div class="mb-4">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Search by name or email..." aria-label="Search customers" class="max-w-sm" />
    </div>

    <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900" wire:loading.class="opacity-50">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                    <th class="px-4 py-3 font-medium">Name</th>
                    <th class="px-4 py-3 font-medium">Email</th>
                    <th class="px-4 py-3 font-medium">Orders</th>
                    <th class="px-4 py-3 font-medium">Total spent</th>
                    <th class="px-4 py-3 font-medium">Created</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($customers as $customer)
                    <tr class="border-b border-zinc-100 dark:border-zinc-800" wire:key="customer-{{ $customer->getKey() }}">
                        <td class="px-4 py-3"><a href="{{ route('admin.customers.show', $customer) }}" class="font-medium underline" wire:navigate>{{ $customer->name }}</a></td>
                        <td class="px-4 py-3">{{ $customer->email }}</td>
                        <td class="px-4 py-3">{{ $customer->orders_count }}</td>
                        <td class="px-4 py-3">{{ $this->money((int) ($customer->orders_sum_total_amount ?? 0)) }}</td>
                        <td class="px-4 py-3">{{ $customer->created_at?->format('M j, Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center"><flux:text variant="subtle">No customers found.</flux:text></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $customers->links() }}
    </div>
</div>
