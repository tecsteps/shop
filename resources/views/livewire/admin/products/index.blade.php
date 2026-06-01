@php
    use App\Enums\ProductStatus;

    $statusColors = ['draft' => 'zinc', 'active' => 'green', 'archived' => 'red'];
@endphp

<div>
    <x-admin.breadcrumbs :items="[['label' => __('Products')]]" />

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl" level="1">{{ __('Products') }}</flux:heading>
        <flux:button variant="primary" icon="plus" :href="route('admin.products.create')" wire:navigate data-test="add-product">
            {{ __('Add product') }}
        </flux:button>
    </div>

    {{-- Filters. --}}
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <flux:input
            wire:model.live.debounce.300ms="search"
            icon="magnifying-glass"
            :placeholder="__('Search products...')"
            class="max-w-xs"
            data-test="product-search"
        />

        <flux:select wire:model.live="statusFilter" class="w-40" data-test="status-filter">
            <flux:select.option value="all">{{ __('All statuses') }}</flux:select.option>
            <flux:select.option value="draft">{{ __('Draft') }}</flux:select.option>
            <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
            <flux:select.option value="archived">{{ __('Archived') }}</flux:select.option>
        </flux:select>

        <flux:select wire:model.live="typeFilter" class="w-40" data-test="type-filter">
            <flux:select.option value="all">{{ __('All types') }}</flux:select.option>
            @foreach ($this->productTypes as $type)
                <flux:select.option :value="$type">{{ $type }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    {{-- Bulk action bar. --}}
    @if (count($selectedIds) > 0)
        <div class="mb-4 flex flex-wrap items-center gap-3 rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-800/50">
            <flux:text class="font-medium">{{ trans_choice(':count product selected|:count products selected', count($selectedIds), ['count' => count($selectedIds)]) }}</flux:text>
            <flux:spacer />
            <flux:button size="sm" variant="ghost" wire:click="bulkSetActive" data-test="bulk-activate">{{ __('Set Active') }}</flux:button>
            <flux:button size="sm" variant="ghost" wire:click="bulkArchive" data-test="bulk-archive">{{ __('Archive') }}</flux:button>
            <flux:button size="sm" variant="danger" wire:click="confirmBulkDelete" data-test="bulk-delete">{{ __('Delete') }}</flux:button>
        </div>
    @endif

    {{-- Empty state. --}}
    @if ($this->products->isEmpty() && $search === '' && $statusFilter === 'all' && $typeFilter === 'all')
        <x-admin.card class="py-16 text-center">
            <flux:icon.cube class="mx-auto size-12 text-zinc-300 dark:text-zinc-600" />
            <flux:heading size="lg" class="mt-4">{{ __('Add your first product') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Start building your catalog by adding products.') }}</flux:text>
            <div class="mt-6">
                <flux:button variant="primary" icon="plus" :href="route('admin.products.create')" wire:navigate>{{ __('Add product') }}</flux:button>
            </div>
        </x-admin.card>
    @else
        <div wire:loading.class="opacity-50">
            <flux:table :paginate="$this->products">
                <flux:table.columns>
                    <flux:table.column>
                        <flux:checkbox wire:model.live="selectAll" wire:click="toggleSelectAll" :aria-label="__('Select all')" />
                    </flux:table.column>
                    <flux:table.column>{{ __('Image') }}</flux:table.column>
                    <flux:table.column sortable :sorted="$sortField === 'title'" :direction="$sortDirection" wire:click="sortBy('title')">{{ __('Title') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                    <flux:table.column>{{ __('Variants') }}</flux:table.column>
                    <flux:table.column>{{ __('Type') }}</flux:table.column>
                    <flux:table.column>{{ __('Vendor') }}</flux:table.column>
                    <flux:table.column sortable :sorted="$sortField === 'updated_at'" :direction="$sortDirection" wire:click="sortBy('updated_at')">{{ __('Updated') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->products as $product)
                        <flux:table.row :key="'product-'.$product->id">
                            <flux:table.cell>
                                <flux:checkbox wire:model.live="selectedIds" :value="$product->id" :aria-label="__('Select product')" />
                            </flux:table.cell>
                            <flux:table.cell>
                                @php($img = $product->media->first())
                                @if ($img)
                                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($img->storage_key) }}" alt="{{ $img->alt_text }}" class="size-10 rounded object-cover" />
                                @else
                                    <div class="flex size-10 items-center justify-center rounded bg-zinc-100 dark:bg-zinc-800">
                                        <flux:icon.photo class="size-5 text-zinc-400" />
                                    </div>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell variant="strong">
                                <flux:link :href="route('admin.products.edit', $product)" wire:navigate>{{ $product->title }}</flux:link>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" :color="$statusColors[$product->status->value] ?? 'zinc'">{{ ucfirst($product->status->value) }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ $product->variants_count }}</flux:table.cell>
                            <flux:table.cell>{{ $product->product_type ?: '—' }}</flux:table.cell>
                            <flux:table.cell>{{ $product->vendor ?: '—' }}</flux:table.cell>
                            <flux:table.cell class="whitespace-nowrap">{{ $product->updated_at?->diffForHumans() }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="8" class="text-center">{{ __('No products match your filters.') }}</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    @endif

    {{-- Delete confirmation modal. --}}
    <flux:modal wire:model.self="showDeleteModal" name="confirm-bulk-delete" class="md:w-96">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Delete products?') }}</flux:heading>
            <flux:text>{{ __('This will archive :count product(s). Products with orders cannot be permanently deleted.', ['count' => count($selectedIds)]) }}</flux:text>
            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="$set('showDeleteModal', false)">{{ __('Cancel') }}</flux:button>
                <flux:button variant="danger" wire:click="bulkDelete" data-test="confirm-bulk-delete">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
