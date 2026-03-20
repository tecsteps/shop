<div>
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl" level="1">Discounts</flux:heading>
        <flux:button variant="primary" href="{{ route('admin.discounts.create') }}" wire:navigate icon="plus">Create discount</flux:button>
    </div>

    <div class="mb-4 flex flex-col gap-4 sm:flex-row">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by code..." icon="magnifying-glass" class="flex-1" />
        <flux:select wire:model.live="statusFilter" class="w-40">
            <flux:select.option value="all">All statuses</flux:select.option>
            <flux:select.option value="active">Active</flux:select.option>
            <flux:select.option value="expired">Expired</flux:select.option>
            <flux:select.option value="disabled">Disabled</flux:select.option>
        </flux:select>
    </div>

    <flux:table :paginate="$this->discounts">
        <flux:table.columns>
            <flux:table.column>Code</flux:table.column>
            <flux:table.column>Type</flux:table.column>
            <flux:table.column>Value</flux:table.column>
            <flux:table.column>Usage</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Dates</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse($this->discounts as $discount)
                <flux:table.row :key="$discount->id">
                    <flux:table.cell variant="strong">
                        <a href="{{ route('admin.discounts.edit', $discount) }}" class="hover:underline" wire:navigate>
                            {{ $discount->code ?? 'Automatic' }}
                        </a>
                    </flux:table.cell>
                    <flux:table.cell>{{ ucfirst($discount->type->value ?? $discount->type) }}</flux:table.cell>
                    <flux:table.cell>
                        @if(($discount->value_type->value ?? $discount->value_type) === 'percent')
                            {{ $discount->value_amount }}%
                        @elseif(($discount->value_type->value ?? $discount->value_type) === 'free_shipping')
                            Free shipping
                        @else
                            ${{ number_format($discount->value_amount / 100, 2) }}
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>{{ $discount->usage_count }}{{ $discount->usage_limit ? '/'.$discount->usage_limit : '' }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="match($discount->status->value ?? $discount->status) { 'active' => 'green', 'expired' => 'red', 'disabled' => 'zinc', default => 'yellow' }">
                            {{ ucfirst($discount->status->value ?? $discount->status) }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap text-xs">
                        {{ $discount->starts_at ? \Carbon\Carbon::parse($discount->starts_at)->format('M d') : '-' }}
                        {{ $discount->ends_at ? ' - '.\Carbon\Carbon::parse($discount->ends_at)->format('M d') : '' }}
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="text-center">
                        <flux:text class="text-zinc-500">No discounts found.</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
