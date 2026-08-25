<div>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <flux:heading size="xl">Discounts</flux:heading>

        <flux:button variant="primary" icon="plus" :href="route('admin.discounts.create')" wire:navigate>
            Create discount
        </flux:button>
    </div>

    <div class="mt-6 flex flex-col gap-3 sm:flex-row">
        <flux:input
            wire:model.live.debounce.300ms="search"
            icon="magnifying-glass"
            placeholder="Search by discount code..."
            class="sm:max-w-sm"
        />

        <flux:select wire:model.live="statusFilter" class="sm:w-44">
            <option value="all">All statuses</option>
            <option value="active">Active</option>
            <option value="scheduled">Scheduled</option>
            <option value="expired">Expired</option>
        </flux:select>
    </div>

    <div wire:loading.delay.class="opacity-50" class="mt-4">
        <flux:card class="overflow-hidden">
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
                    @forelse ($this->discounts as $discount)
                        <flux:table.row :key="$discount->id">
                            <flux:table.cell variant="strong">
                                <a href="{{ route('admin.discounts.edit', $discount) }}" wire:navigate class="hover:underline">
                                    {{ $discount->type === 'code' ? $discount->code : 'Automatic' }}
                                </a>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge :color="$discount->type === 'code' ? 'blue' : 'purple'" size="sm">
                                    {{ $discount->type === 'code' ? 'Code' : 'Automatic' }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                @switch($discount->value_type)
                                    @case('percent')
                                        {{ $discount->value_amount }}%
                                        @break
                                    @case('fixed')
                                        {{ $this->formatMoney((int) $discount->value_amount) }}
                                        @break
                                    @default
                                        Free shipping
                                @endswitch
                            </flux:table.cell>
                            <flux:table.cell>
                                {{ $discount->usage_count }}
                                / {{ $discount->usage_limit ?? 'unlimited' }}
                            </flux:table.cell>
                            <flux:table.cell>
                                @php
                                    $status = $this->displayStatus($discount);
                                    $colors = ['active' => 'green', 'expired' => 'red', 'scheduled' => 'yellow', 'disabled' => 'zinc', 'draft' => 'zinc'];
                                @endphp
                                <flux:badge :color="$colors[$status] ?? 'zinc'" size="sm">
                                    {{ ucfirst($status) }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <span class="text-xs">
                                    {{ $discount->starts_at?->format('M j, Y') ?? '—' }}
                                    @if ($discount->ends_at)
                                        → {{ $discount->ends_at->format('M j, Y') }}
                                    @endif
                                </span>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6" align="center" class="py-10 text-zinc-400">
                                No discounts match your filters.
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>
    </div>
</div>
