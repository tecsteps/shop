<div class="space-y-4">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Products</flux:heading>
        <flux:button variant="primary" href="{{ url('/admin/products/create') }}">Add product</flux:button>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search products..." icon="magnifying-glass" class="sm:max-w-xs" />
        <flux:select wire:model.live="statusFilter">
            @foreach ($statuses as $status)
                <flux:select.option value="{{ $status }}">{{ ucfirst($status) }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="overflow-x-auto rounded-lg border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
        <table class="w-full text-sm">
            <thead class="text-left text-xs uppercase text-neutral-500">
                <tr>
                    <th class="px-4 py-2">Title</th>
                    <th class="px-4 py-2">Status</th>
                    <th class="px-4 py-2">Inventory</th>
                    <th class="px-4 py-2">Vendor</th>
                    <th class="px-4 py-2">Created</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    @php
                        $inventory = $product->variants->sum(fn ($variant) => optional($variant->inventoryItem)->quantity_on_hand ?? 0);
                        $statusColor = match ($product->status->value) {
                            'active' => 'green',
                            'draft' => 'zinc',
                            'archived' => 'red',
                            default => 'zinc',
                        };
                    @endphp
                    <tr wire:key="product-{{ $product->id }}" class="border-t border-neutral-100 dark:border-neutral-800">
                        <td class="px-4 py-2">
                            <a href="{{ url('/admin/products/'.$product->id.'/edit') }}" class="font-medium hover:underline">{{ $product->title }}</a>
                        </td>
                        <td class="px-4 py-2">
                            <flux:badge color="{{ $statusColor }}" size="sm">{{ ucfirst($product->status->value) }}</flux:badge>
                        </td>
                        <td class="px-4 py-2">{{ $inventory }}</td>
                        <td class="px-4 py-2">{{ $product->vendor ?? '-' }}</td>
                        <td class="px-4 py-2 text-neutral-500">{{ $product->created_at?->format('Y-m-d') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-neutral-500">No products match your filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $products->links() }}</div>
</div>
