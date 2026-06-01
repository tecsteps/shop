@php
    use App\Support\Storefront\PriceFormatter;

    $currency = $currentStore->default_currency ?? 'USD';
@endphp

<div>
    <x-admin.breadcrumbs :items="[['label' => __('Customers')]]" />

    <flux:heading size="xl" level="1" class="mb-6">{{ __('Customers') }}</flux:heading>

    <div class="mb-4 max-w-md">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search by name or email...')" data-test="customer-search" />
    </div>

    <flux:table :paginate="$this->customers">
        <flux:table.columns>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column>{{ __('Email') }}</flux:table.column>
            <flux:table.column>{{ __('Orders') }}</flux:table.column>
            <flux:table.column class="text-right">{{ __('Total spent') }}</flux:table.column>
            <flux:table.column>{{ __('Created') }}</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->customers as $customer)
                <flux:table.row :key="'cust-'.$customer->id">
                    <flux:table.cell variant="strong">
                        <flux:link :href="route('admin.customers.show', $customer)" wire:navigate>{{ $customer->name ?: __('Unnamed') }}</flux:link>
                    </flux:table.cell>
                    <flux:table.cell>{{ $customer->email }}</flux:table.cell>
                    <flux:table.cell>{{ $customer->orders_count }}</flux:table.cell>
                    <flux:table.cell class="text-right">{{ PriceFormatter::format((int) ($customer->orders_sum_total_amount ?? 0), $currency) }}</flux:table.cell>
                    <flux:table.cell>{{ $customer->created_at?->format('M j, Y') }}</flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="text-center">{{ __('No customers found.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
