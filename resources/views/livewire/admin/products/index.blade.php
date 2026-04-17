<div>
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ __('Products') }}</flux:heading>
        <flux:button variant="primary" :href="route('admin.products.create')" wire:navigate>
            {{ __('Add product') }}
        </flux:button>
    </div>

    <div class="mt-4 flex flex-wrap items-center gap-4">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search products...') }}" class="w-64" icon="magnifying-glass" />
        <flux:select wire:model.live="statusFilter" class="w-40">
            <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
            <flux:select.option value="draft">{{ __('Draft') }}</flux:select.option>
            <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
            <flux:select.option value="archived">{{ __('Archived') }}</flux:select.option>
        </flux:select>

        @if(count($selected) > 0)
            <flux:button wire:click="archiveSelected" size="sm">{{ __('Archive') }}</flux:button>
            <flux:button wire:click="confirmDelete" size="sm" variant="danger">{{ __('Delete') }}</flux:button>
        @endif
    </div>

    <flux:table class="mt-4">
        <flux:table.columns>
            <flux:table.column class="w-8">
                <flux:checkbox wire:model.live="selectAll" />
            </flux:table.column>
            <flux:table.column>{{ __('Product') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column>{{ __('Vendor') }}</flux:table.column>
            <flux:table.column align="end">{{ __('Price') }}</flux:table.column>
            <flux:table.column align="end">{{ __('Inventory') }}</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse($products as $product)
                <flux:table.row :key="$product->id">
                    <flux:table.cell>
                        <flux:checkbox wire:model.live="selected" value="{{ $product->id }}" />
                    </flux:table.cell>
                    <flux:table.cell variant="strong">
                        <a href="{{ route('admin.products.edit', $product) }}" wire:navigate class="hover:underline">
                            {{ $product->title }}
                        </a>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="match($product->status->value) {
                            'active' => 'green',
                            'draft' => 'yellow',
                            'archived' => 'zinc',
                            default => 'zinc',
                        }">{{ ucfirst($product->status->value) }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ $product->vendor ?? '-' }}</flux:table.cell>
                    <flux:table.cell align="end">
                        @php
                            $defaultVariant = $product->variants->firstWhere('is_default', true);
                        @endphp
                        ${{ number_format(($defaultVariant?->price_amount ?? 0) / 100, 2) }}
                    </flux:table.cell>
                    <flux:table.cell align="end">
                        @php
                            $totalStock = $product->variants->sum(fn ($v) => $v->inventoryItem?->quantity_on_hand ?? 0);
                        @endphp
                        {{ $totalStock }}
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6">
                        <flux:text class="text-center">{{ __('No products found.') }}</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <div class="mt-4">
        {{ $products->links() }}
    </div>

    <flux:modal wire:model.live="showDeleteModal" name="delete-confirmation" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Delete Products') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Are you sure you want to delete the selected products? Only draft products can be deleted.') }}</flux:text>
            </div>
            <div class="flex justify-end gap-2">
                <flux:button wire:click="$set('showDeleteModal', false)">{{ __('Cancel') }}</flux:button>
                <flux:button variant="danger" wire:click="deleteSelected">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
