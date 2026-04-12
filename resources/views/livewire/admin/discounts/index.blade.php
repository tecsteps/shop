<div class="space-y-6 p-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Discounts</flux:heading>
        <flux:button :href="route('admin.discounts.create')" variant="primary" icon="plus" wire:navigate>New discount</flux:button>
    </div>

    <div class="flex gap-3">
        <flux:select wire:model.live="statusFilter" class="w-44">
            <flux:select.option value="">All statuses</flux:select.option>
            <flux:select.option value="draft">Draft</flux:select.option>
            <flux:select.option value="active">Active</flux:select.option>
            <flux:select.option value="disabled">Disabled</flux:select.option>
            <flux:select.option value="expired">Expired</flux:select.option>
        </flux:select>
        <flux:select wire:model.live="typeFilter" class="w-44">
            <flux:select.option value="">All types</flux:select.option>
            <flux:select.option value="code">Code</flux:select.option>
            <flux:select.option value="automatic">Automatic</flux:select.option>
        </flux:select>
    </div>

    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        @if ($discounts->isEmpty())
            <div class="p-12 text-center text-sm text-zinc-500">No discounts yet.</div>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Code</flux:table.column>
                    <flux:table.column>Type</flux:table.column>
                    <flux:table.column>Value</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Usage</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($discounts as $discount)
                        <flux:table.row>
                            <flux:table.cell>
                                <a href="{{ route('admin.discounts.edit', $discount) }}" class="font-medium hover:underline" wire:navigate>
                                    {{ $discount->code ?? 'Automatic' }}
                                </a>
                            </flux:table.cell>
                            <flux:table.cell>{{ $discount->type->value }}</flux:table.cell>
                            <flux:table.cell>
                                @if ($discount->value_type->value === 'percent')
                                    {{ $discount->value_amount }}%
                                @elseif ($discount->value_type->value === 'free_shipping')
                                    Free shipping
                                @else
                                    {{ number_format($discount->value_amount / 100, 2) }}
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge :color="$discount->status->value === 'active' ? 'green' : 'zinc'">
                                    {{ $discount->status->value }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ $discount->usage_count }}{{ $discount->usage_limit !== null ? '/'.$discount->usage_limit : '' }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:button size="sm" variant="ghost" :href="route('admin.discounts.edit', $discount)" wire:navigate>Edit</flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
            <div class="p-4">{{ $discounts->links() }}</div>
        @endif
    </div>
</div>
