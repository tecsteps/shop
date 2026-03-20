<div>
    <div class="mb-6">
        <flux:heading size="xl" level="1">Customers</flux:heading>
    </div>

    <div class="mb-4">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by name or email..." icon="magnifying-glass" />
    </div>

    <flux:table :paginate="$this->customers">
        <flux:table.columns>
            <flux:table.column>Name</flux:table.column>
            <flux:table.column>Email</flux:table.column>
            <flux:table.column>Orders</flux:table.column>
            <flux:table.column>Total spent</flux:table.column>
            <flux:table.column>Created</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse($this->customers as $customer)
                <flux:table.row :key="$customer->id">
                    <flux:table.cell variant="strong">
                        <a href="{{ route('admin.customers.show', $customer) }}" class="hover:underline" wire:navigate>{{ $customer->name }}</a>
                    </flux:table.cell>
                    <flux:table.cell>{{ $customer->email }}</flux:table.cell>
                    <flux:table.cell>{{ $customer->orders_count }}</flux:table.cell>
                    <flux:table.cell>{{ $this->formatCurrency((int) ($customer->orders_sum_total_amount ?? 0)) }}</flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">{{ $customer->created_at->format('M d, Y') }}</flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="text-center">
                        <flux:text class="text-zinc-500">No customers found.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
