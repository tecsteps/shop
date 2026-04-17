<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Products</flux:heading>
        <flux:button href="{{ route('admin.products.create') }}" variant="primary" icon="plus" wire:navigate>New product</flux:button>
    </div>

    <div class="flex items-center gap-3">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search products..." icon="magnifying-glass" class="flex-1" />
        <flux:select wire:model.live="status">
            <flux:select.option value="all">All statuses</flux:select.option>
            <flux:select.option value="draft">Draft</flux:select.option>
            <flux:select.option value="active">Active</flux:select.option>
            <flux:select.option value="archived">Archived</flux:select.option>
        </flux:select>
    </div>

    <div class="rounded-xl bg-white ring-1 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700">
        @if ($products->isEmpty())
            <div class="p-10 text-center text-zinc-500">No products yet.</div>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Title</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Vendor</flux:table.column>
                    <flux:table.column>Type</flux:table.column>
                    <flux:table.column>Variants</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($products as $product)
                        <flux:table.row data-testid="product-row">
                            <flux:table.cell>
                                <div class="font-medium">{{ $product->title }}</div>
                                <div class="text-xs text-zinc-500">{{ $product->handle }}</div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" :color="$product->status->value === 'active' ? 'emerald' : 'zinc'">{{ $product->status->value }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ $product->vendor }}</flux:table.cell>
                            <flux:table.cell>{{ $product->product_type }}</flux:table.cell>
                            <flux:table.cell>{{ $product->variants()->count() }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:button size="xs" variant="ghost" href="{{ route('admin.products.edit', $product) }}" wire:navigate>Edit</flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </div>

    <div>{{ $products->links() }}</div>
</div>
