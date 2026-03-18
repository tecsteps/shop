<div>
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">{{ __('Products') }}</flux:heading>
        <flux:button variant="primary" :href="route('admin.products.create')" wire:navigate>
            {{ __('Add product') }}
        </flux:button>
    </div>

    <div class="flex flex-col sm:flex-row gap-4 mb-4">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search products...') }}" icon="magnifying-glass" />
        </div>
        <flux:select wire:model.live="statusFilter" class="w-full sm:w-48">
            <flux:select.option value="all">{{ __('All statuses') }}</flux:select.option>
            <flux:select.option value="draft">{{ __('Draft') }}</flux:select.option>
            <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
            <flux:select.option value="archived">{{ __('Archived') }}</flux:select.option>
        </flux:select>
    </div>

    @if(count($selectedIds) > 0)
        <div class="flex items-center gap-4 mb-4 p-3 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
            <flux:text>{{ count($selectedIds) }} {{ __('products selected') }}</flux:text>
            <flux:button size="sm" variant="ghost" wire:click="bulkSetActive">{{ __('Set Active') }}</flux:button>
            <flux:button size="sm" variant="ghost" wire:click="bulkArchive">{{ __('Archive') }}</flux:button>
            <flux:button size="sm" variant="danger" wire:click="bulkDelete" wire:confirm="{{ __('Are you sure you want to delete the selected products?') }}">{{ __('Delete') }}</flux:button>
        </div>
    @endif

    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 overflow-hidden">
        @if($this->products->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                            <th class="p-3 w-10">
                                <flux:checkbox wire:click="toggleSelectAll" :checked="$selectAll" />
                            </th>
                            <th class="p-3 text-left font-medium text-zinc-500 cursor-pointer" wire:click="sortBy('title')">
                                {{ __('Title') }}
                                @if($sortField === 'title')
                                    <span>{{ $sortDirection === 'asc' ? '&#9650;' : '&#9660;' }}</span>
                                @endif
                            </th>
                            <th class="p-3 text-left font-medium text-zinc-500">{{ __('Status') }}</th>
                            <th class="p-3 text-left font-medium text-zinc-500">{{ __('Variants') }}</th>
                            <th class="p-3 text-left font-medium text-zinc-500">{{ __('Type') }}</th>
                            <th class="p-3 text-left font-medium text-zinc-500">{{ __('Vendor') }}</th>
                            <th class="p-3 text-left font-medium text-zinc-500 cursor-pointer" wire:click="sortBy('updated_at')">
                                {{ __('Updated') }}
                                @if($sortField === 'updated_at')
                                    <span>{{ $sortDirection === 'asc' ? '&#9650;' : '&#9660;' }}</span>
                                @endif
                            </th>
                        </tr>
                    </thead>
                    <tbody wire:loading.class="opacity-50">
                        @foreach($this->products as $product)
                            <tr class="border-b border-zinc-100 dark:border-zinc-800">
                                <td class="p-3">
                                    <flux:checkbox wire:model.live="selectedIds" value="{{ $product->id }}" />
                                </td>
                                <td class="p-3">
                                    <a href="{{ route('admin.products.edit', $product) }}" class="text-accent hover:underline" wire:navigate>
                                        {{ $product->title }}
                                    </a>
                                </td>
                                <td class="p-3">
                                    <flux:badge size="sm" :color="match($product->status) {
                                        \App\Enums\ProductStatus::Active => 'green',
                                        \App\Enums\ProductStatus::Draft => 'zinc',
                                        \App\Enums\ProductStatus::Archived => 'red',
                                    }">{{ ucfirst($product->status->value) }}</flux:badge>
                                </td>
                                <td class="p-3">{{ $product->variants_count }}</td>
                                <td class="p-3">{{ $product->product_type ?? '-' }}</td>
                                <td class="p-3">{{ $product->vendor ?? '-' }}</td>
                                <td class="p-3 text-zinc-500">{{ $product->updated_at->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4">
                {{ $this->products->links() }}
            </div>
        @else
            <div class="p-12 text-center">
                <flux:icon name="cube" class="mx-auto h-12 w-12 text-zinc-400" />
                <flux:heading size="lg" class="mt-4">{{ __('Add your first product') }}</flux:heading>
                <flux:text class="mt-2 text-zinc-500">{{ __('Start building your catalog by adding products.') }}</flux:text>
                <div class="mt-6">
                    <flux:button variant="primary" :href="route('admin.products.create')" wire:navigate>
                        {{ __('Add product') }}
                    </flux:button>
                </div>
            </div>
        @endif
    </div>
</div>
