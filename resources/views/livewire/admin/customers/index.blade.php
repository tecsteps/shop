<div>
    <flux:heading size="xl">{{ __('Customers') }}</flux:heading>

    <div class="mt-4">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search customers...') }}" class="w-64" icon="magnifying-glass" />
    </div>

    <flux:table class="mt-4">
        <flux:table.columns>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column>{{ __('Email') }}</flux:table.column>
            <flux:table.column>{{ __('Joined') }}</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse($customers as $customer)
                <flux:table.row :key="$customer->id">
                    <flux:table.cell variant="strong">
                        <a href="{{ route('admin.customers.show', $customer) }}" wire:navigate class="hover:underline">
                            {{ $customer->name }}
                        </a>
                    </flux:table.cell>
                    <flux:table.cell>{{ $customer->email }}</flux:table.cell>
                    <flux:table.cell>{{ $customer->created_at->format('M d, Y') }}</flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="3">
                        <flux:text class="text-center">{{ __('No customers found.') }}</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <div class="mt-4">
        {{ $customers->links() }}
    </div>
</div>
