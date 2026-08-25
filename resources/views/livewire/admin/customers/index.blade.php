<div>
    <flux:heading size="xl">Customers</flux:heading>

    <div class="mt-6">
        <flux:input
            wire:model.live.debounce.300ms="search"
            icon="magnifying-glass"
            placeholder="Search by name or email..."
            class="max-w-sm"
        />
    </div>

    <div wire:loading.delay.class="opacity-50" class="mt-4">
        <flux:card class="overflow-hidden">
            <flux:table :paginate="$this->customers">
                <flux:table.columns>
                    <flux:table.column>Name</flux:table.column>
                    <flux:table.column>Email</flux:table.column>
                    <flux:table.column>Orders</flux:table.column>
                    <flux:table.column>Total spent</flux:table.column>
                    <flux:table.column>Created</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->customers as $customer)
                        <flux:table.row :key="$customer->id">
                            <flux:table.cell variant="strong">
                                <a href="{{ route('admin.customers.show', $customer) }}" wire:navigate class="hover:underline">
                                    {{ $customer->name }}
                                </a>
                            </flux:table.cell>
                            <flux:table.cell>{{ $customer->email }}</flux:table.cell>
                            <flux:table.cell>{{ $customer->orders_count }}</flux:table.cell>
                            <flux:table.cell>{{ $this->formatMoney((int) $customer->orders_sum_total_amount) }}</flux:table.cell>
                            <flux:table.cell>{{ $customer->created_at?->format('M j, Y') }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5" align="center" class="py-10 text-zinc-400">
                                No customers found.
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>
    </div>
</div>
