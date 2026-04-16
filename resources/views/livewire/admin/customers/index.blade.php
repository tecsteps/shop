<div class="flex flex-col gap-6">
    <flux:heading size="xl">Customers</flux:heading>

    <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by name or email..." icon="magnifying-glass" />

    <div class="rounded-xl bg-white ring-1 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700">
        @if ($customers->isEmpty())
            <div class="p-10 text-center text-zinc-500">No customers yet.</div>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Name</flux:table.column>
                    <flux:table.column>Email</flux:table.column>
                    <flux:table.column>Orders</flux:table.column>
                    <flux:table.column>LTV</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($customers as $customer)
                        <flux:table.row>
                            <flux:table.cell><a href="{{ route('admin.customers.show', $customer) }}" class="font-medium hover:underline" wire:navigate>{{ $customer->name ?? 'No name' }}</a></flux:table.cell>
                            <flux:table.cell>{{ $customer->email }}</flux:table.cell>
                            <flux:table.cell>{{ $customer->orders_count }}</flux:table.cell>
                            <flux:table.cell>{{ number_format((int) ($customer->lifetime_value_amount ?? 0) / 100, 2) }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </div>

    <div>{{ $customers->links() }}</div>
</div>
