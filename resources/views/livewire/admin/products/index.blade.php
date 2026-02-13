<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Products</flux:heading>
        <a href="{{ route('admin.products.create') }}">
            <flux:button variant="primary">Create Product</flux:button>
        </a>
    </div>

    <div class="flex items-center gap-4">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search products..." />
        <flux:select wire:model.live="statusFilter">
            <option value="">All Statuses</option>
            <option value="draft">Draft</option>
            <option value="active">Active</option>
            <option value="archived">Archived</option>
        </flux:select>
    </div>

    @if (count($selected) > 0)
        <div class="flex items-center gap-3 rounded-md bg-blue-50 p-3 dark:bg-blue-900/30">
            <flux:text>{{ count($selected) }} selected</flux:text>
            <flux:button wire:click="bulkArchive" variant="ghost" size="sm">Archive Selected</flux:button>
        </div>
    @endif

    <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                    <th class="px-4 py-3 font-medium"><input type="checkbox" class="rounded" /></th>
                    <th class="px-4 py-3 font-medium">Title</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium">Vendor</th>
                    <th class="px-4 py-3 font-medium">Type</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($products as $product)
                    <tr wire:key="product-{{ $product->id }}" class="border-b border-zinc-100 dark:border-zinc-800">
                        <td class="px-4 py-3">
                            <input type="checkbox" wire:model.live="selected" value="{{ $product->id }}" class="rounded" />
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.products.edit', $product) }}" class="text-blue-600 hover:underline dark:text-blue-400">{{ $product->title }}</a>
                        </td>
                        <td class="px-4 py-3"><flux:badge size="sm">{{ ucfirst($product->status->value) }}</flux:badge></td>
                        <td class="px-4 py-3">{{ $product->vendor }}</td>
                        <td class="px-4 py-3">{{ $product->product_type }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div>{{ $products->links() }}</div>
</div>
