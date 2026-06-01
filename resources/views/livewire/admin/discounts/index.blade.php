@php
    use App\Support\Storefront\PriceFormatter;

    $statusColors = ['draft' => 'zinc', 'active' => 'green', 'expired' => 'red', 'disabled' => 'zinc'];
    $currency = $currentStore->default_currency ?? 'USD';
@endphp

<div>
    <x-admin.breadcrumbs :items="[['label' => __('Discounts')]]" />

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl" level="1">{{ __('Discounts') }}</flux:heading>
        <flux:button variant="primary" icon="plus" :href="route('admin.discounts.create')" wire:navigate data-test="add-discount">
            {{ __('Create discount') }}
        </flux:button>
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-3">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search by code...')" class="max-w-xs" />
        <flux:select wire:model.live="statusFilter" class="w-40">
            <flux:select.option value="all">{{ __('All') }}</flux:select.option>
            <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
            <flux:select.option value="expired">{{ __('Expired') }}</flux:select.option>
            <flux:select.option value="scheduled">{{ __('Scheduled') }}</flux:select.option>
        </flux:select>
    </div>

    @if ($this->discounts->isEmpty() && $search === '' && $statusFilter === 'all')
        <x-admin.card class="py-16 text-center">
            <flux:icon.tag class="mx-auto size-12 text-zinc-300 dark:text-zinc-600" />
            <flux:heading size="lg" class="mt-4">{{ __('Create your first discount') }}</flux:heading>
            <div class="mt-6">
                <flux:button variant="primary" icon="plus" :href="route('admin.discounts.create')" wire:navigate>{{ __('Create discount') }}</flux:button>
            </div>
        </x-admin.card>
    @else
        <flux:table :paginate="$this->discounts">
            <flux:table.columns>
                <flux:table.column>{{ __('Code') }}</flux:table.column>
                <flux:table.column>{{ __('Type') }}</flux:table.column>
                <flux:table.column>{{ __('Value') }}</flux:table.column>
                <flux:table.column>{{ __('Usage') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Dates') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($this->discounts as $discount)
                    <flux:table.row :key="'disc-'.$discount->id">
                        <flux:table.cell variant="strong">
                            <flux:link :href="route('admin.discounts.edit', $discount)" wire:navigate>
                                {{ $discount->code ?: __('Automatic') }}
                            </flux:link>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" color="zinc">{{ $discount->type->value === 'code' ? __('Code') : __('Automatic') }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            @switch($discount->value_type->value)
                                @case('percent') {{ $discount->value_amount }}% @break
                                @case('fixed') {{ PriceFormatter::format((int) $discount->value_amount, $currency) }} @break
                                @case('free_shipping') {{ __('Free shipping') }} @break
                            @endswitch
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ $discount->usage_count }} / {{ $discount->usage_limit ?? __('unlimited') }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$statusColors[$discount->status->value] ?? 'zinc'">{{ ucfirst($discount->status->value) }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap text-sm">
                            {{ $discount->starts_at?->format('M j, Y') ?? '—' }}
                            @if ($discount->ends_at) – {{ $discount->ends_at->format('M j, Y') }} @endif
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</div>
