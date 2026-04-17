<div class="space-y-6 p-6">
    <flux:heading size="xl">Customers</flux:heading>

    <flux:input wire:model.live.debounce.300ms="search" placeholder="Search customers..." icon="magnifying-glass" class="w-72" />

    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        @if ($customers->isEmpty())
            <div class="p-12 text-center text-sm text-zinc-500">No customers found.</div>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Name</flux:table.column>
                    <flux:table.column>Email</flux:table.column>
                    <flux:table.column>Orders</flux:table.column>
                    <flux:table.column>Total spent</flux:table.column>
                    <flux:table.column>Joined</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($customers as $customer)
                        <flux:table.row>
                            <flux:table.cell>
                                <a href="{{ route('admin.customers.show', $customer) }}" class="font-medium text-zinc-900 hover:underline dark:text-white" wire:navigate>
                                    {{ $customer->name ?? 'Guest' }}
                                </a>
                            </flux:table.cell>
                            <flux:table.cell>{{ $customer->email }}</flux:table.cell>
                            <flux:table.cell>{{ $customer->orders_count }}</flux:table.cell>
                            <flux:table.cell>{{ number_format((int) ($customer->total_spent ?? 0) / 100, 2) }}</flux:table.cell>
                            <flux:table.cell>{{ $customer->created_at?->format('M d, Y') }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
            <div class="p-4">{{ $customers->links() }}</div>
        @endif
    </div>
</div>
