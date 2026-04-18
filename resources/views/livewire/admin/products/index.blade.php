<div class="space-y-4">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Products</flux:heading>
        <flux:button href="{{ route('admin.products.create') }}" variant="primary" wire:navigate>New product</flux:button>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search products..." />
        <flux:select wire:model.live="status" placeholder="All statuses">
            <flux:select.option value="">All statuses</flux:select.option>
            @foreach ($statuses as $case)
                <flux:select.option value="{{ $case->value }}">{{ ucfirst($case->value) }}</flux:select.option>
            @endforeach
        </flux:select>
        @if (count($selected) > 0)
            <flux:dropdown>
                <flux:button>Bulk actions ({{ count($selected) }})</flux:button>
                <flux:menu>
                    <flux:menu.item wire:click="bulkArchive">Archive</flux:menu.item>
                    <flux:menu.item wire:click="bulkDelete">Delete</flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        @endif
    </div>

    <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-zinc-900">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 text-left text-zinc-500 dark:bg-zinc-800">
                <tr>
                    <th class="w-10 p-3"></th>
                    <th class="p-3">Title</th>
                    <th class="p-3">Status</th>
                    <th class="p-3">Vendor</th>
                    <th class="p-3">Type</th>
                    <th class="p-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    <tr wire:key="product-{{ $product->id }}" class="border-t border-zinc-100 dark:border-zinc-800">
                        <td class="p-3"><flux:checkbox wire:model.live="selected" value="{{ $product->id }}" /></td>
                        <td class="p-3">
                            <a class="text-sky-600 hover:underline" href="{{ route('admin.products.edit', $product) }}" wire:navigate>{{ $product->title }}</a>
                        </td>
                        <td class="p-3">{{ $product->status?->value }}</td>
                        <td class="p-3">{{ $product->vendor }}</td>
                        <td class="p-3">{{ $product->product_type }}</td>
                        <td class="p-3 text-right">
                            <flux:button size="sm" variant="ghost" href="{{ route('admin.products.edit', $product) }}" wire:navigate>Edit</flux:button>
                        </td>
                    </tr>
                @empty
                    <tr><td class="p-4 text-zinc-500" colspan="6">No products found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $products->links() }}</div>
</div>
