<div class="space-y-6 p-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Products</flux:heading>
        <flux:button :href="route('admin.products.create')" variant="primary" icon="plus" wire:navigate>New product</flux:button>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search products..." icon="magnifying-glass" class="w-72" />
        <flux:select wire:model.live="statusFilter" class="w-44">
            <flux:select.option value="">All statuses</flux:select.option>
            <flux:select.option value="draft">Draft</flux:select.option>
            <flux:select.option value="active">Active</flux:select.option>
            <flux:select.option value="archived">Archived</flux:select.option>
        </flux:select>
    </div>

    @if (count($selectedIds) > 0)
        <div class="flex items-center gap-3 rounded-md border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-800">
            <span class="text-sm">{{ count($selectedIds) }} selected</span>
            <flux:button size="sm" wire:click="bulkArchive">Archive</flux:button>
            <flux:button size="sm" variant="danger" wire:click="bulkDelete">Delete drafts</flux:button>
        </div>
    @endif

    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        @if ($products->isEmpty())
            <div class="p-12 text-center text-sm text-zinc-500">
                No products match your filters.
            </div>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Title</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Vendor</flux:table.column>
                    <flux:table.column>Variants</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($products as $product)
                        <flux:table.row>
                            <flux:table.cell>
                                <a href="{{ route('admin.products.edit', $product) }}" class="font-medium text-zinc-900 hover:underline dark:text-white" wire:navigate>
                                    {{ $product->title }}
                                </a>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge :color="$product->status->value === 'active' ? 'green' : ($product->status->value === 'draft' ? 'yellow' : 'zinc')">
                                    {{ $product->status->value }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ $product->vendor ?? '-' }}</flux:table.cell>
                            <flux:table.cell>{{ $product->variants_count }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:button size="sm" variant="ghost" :href="route('admin.products.edit', $product)" wire:navigate>Edit</flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>

            <div class="p-4">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</div>
