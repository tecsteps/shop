<div>
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ __('Discounts') }}</flux:heading>
        <flux:button variant="primary" :href="route('admin.discounts.create')" wire:navigate>
            {{ __('Add discount') }}
        </flux:button>
    </div>

    <div class="mt-4 flex flex-wrap items-center gap-4">
        <flux:select wire:model.live="statusFilter" class="w-40">
            <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
            <flux:select.option value="draft">{{ __('Draft') }}</flux:select.option>
            <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
            <flux:select.option value="expired">{{ __('Expired') }}</flux:select.option>
            <flux:select.option value="disabled">{{ __('Disabled') }}</flux:select.option>
        </flux:select>
        <flux:select wire:model.live="typeFilter" class="w-40">
            <flux:select.option value="">{{ __('All types') }}</flux:select.option>
            <flux:select.option value="code">{{ __('Code') }}</flux:select.option>
            <flux:select.option value="automatic">{{ __('Automatic') }}</flux:select.option>
        </flux:select>
    </div>

    <flux:table class="mt-4">
        <flux:table.columns>
            <flux:table.column>{{ __('Code') }}</flux:table.column>
            <flux:table.column>{{ __('Type') }}</flux:table.column>
            <flux:table.column>{{ __('Value') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column>{{ __('Usage') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse($discounts as $discount)
                <flux:table.row :key="$discount->id">
                    <flux:table.cell variant="strong">
                        <a href="{{ route('admin.discounts.edit', $discount) }}" wire:navigate class="hover:underline">
                            {{ $discount->code }}
                        </a>
                    </flux:table.cell>
                    <flux:table.cell>{{ ucfirst($discount->type->value) }}</flux:table.cell>
                    <flux:table.cell>
                        @if($discount->value_type->value === 'percent')
                            {{ $discount->value_amount }}%
                        @elseif($discount->value_type->value === 'fixed')
                            ${{ number_format($discount->value_amount / 100, 2) }}
                        @else
                            {{ __('Free shipping') }}
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="match($discount->status->value) {
                            'active' => 'green', 'draft' => 'yellow', 'expired' => 'red', 'disabled' => 'zinc', default => 'zinc',
                        }">{{ ucfirst($discount->status->value) }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ $discount->usage_count }}{{ $discount->usage_limit ? '/' . $discount->usage_limit : '' }}</flux:table.cell>
                    <flux:table.cell align="end">
                        <flux:button wire:click="delete({{ $discount->id }})" size="sm" variant="ghost" wire:confirm="{{ __('Delete this discount?') }}">
                            <flux:icon name="trash" variant="mini" />
                        </flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6">
                        <flux:text class="text-center">{{ __('No discounts found.') }}</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <div class="mt-4">
        {{ $discounts->links() }}
    </div>
</div>
