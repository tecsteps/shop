<div class="space-y-6">
    <x-admin.page-header title="Products" description="Manage the catalog, pricing, options, and inventory.">
        <x-slot:actions>@if($adminRole !== 'support')<flux:button href="{{ url('/admin/products/create') }}" wire:navigate variant="primary" icon="plus">Add product</flux:button>@endif</x-slot:actions>
    </x-admin.page-header>

    <x-admin.list-toolbar :selected-count="count($selectedIds)">
        <x-slot:search><flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Search products…" aria-label="Search products" /></x-slot:search>
        <x-slot:filters>
            <flux:select wire:model.live="statusFilter" aria-label="Filter by status"><flux:select.option value="all">All statuses</flux:select.option><flux:select.option value="draft">Draft</flux:select.option><flux:select.option value="active">Active</flux:select.option><flux:select.option value="archived">Archived</flux:select.option></flux:select>
            <flux:select wire:model.live="typeFilter" aria-label="Filter by product type"><flux:select.option value="">All types</flux:select.option>@foreach($this->productTypes as $type)<flux:select.option :value="$type">{{ $type }}</flux:select.option>@endforeach</flux:select>
        </x-slot:filters>
    </x-admin.list-toolbar>

    @if(count($selectedIds) && $adminRole !== 'support')
        <div class="admin-bulk-bar" role="region" aria-label="Bulk product actions">
            <p class="text-sm font-medium">{{ count($selectedIds) }} {{ Str::plural('product', count($selectedIds)) }} selected</p>
            <div class="flex flex-wrap gap-2">
                <flux:button wire:click="bulkSetActive" size="sm">Set active</flux:button>
                <flux:button wire:click="bulkArchive" size="sm">Archive</flux:button>
                @if(in_array($adminRole, ['owner', 'admin']))
                    <x-admin.confirmation-modal name="confirm-bulk-delete" title="Delete products?" :description="'This will remove or archive '.count($selectedIds).' selected product(s).'" confirm-action="bulkDelete" confirm-label="Delete products">
                        <x-slot:trigger><flux:button variant="danger" size="sm">Delete</flux:button></x-slot:trigger>
                    </x-admin.confirmation-modal>
                @endif
            </div>
        </div>
    @endif

    @if($this->products->isEmpty() && $search === '' && $statusFilter === 'all' && $typeFilter === '')
        <x-admin.empty-state title="Add your first product" description="Start building your catalog by adding products."><x-slot:action><flux:button href="{{ url('/admin/products/create') }}" wire:navigate variant="primary">Add product</flux:button></x-slot:action></x-admin.empty-state>
    @else
        <x-admin.table-shell caption="Products" loading-target="search,statusFilter,typeFilter,sortBy">
            <x-slot:head><tr><th class="w-10"><input type="checkbox" wire:click="toggleSelectAll" @checked($selectAll) @disabled($adminRole === 'support') aria-label="Select all visible products" class="rounded border-slate-300"></th><th class="w-14">Image</th><th><button wire:click="sortBy('title')" class="font-semibold hover:text-blue-700">Title</button></th><th>Status</th><th>Inventory</th><th>Type</th><th>Vendor</th><th><button wire:click="sortBy('updated_at')" class="font-semibold hover:text-blue-700">Updated</button></th></tr></x-slot:head>
            @forelse($this->products as $product)
                @php $inventory = $product->variants->sum(fn($variant) => (int) ($variant->inventoryItem?->quantity_on_hand ?? 0)); $image = $product->media->first(); @endphp
                <tr wire:key="product-{{ $product->id }}">
                    <td><input type="checkbox" wire:model.live="selectedIds" value="{{ $product->id }}" @disabled($adminRole === 'support') aria-label="Select {{ $product->title }}" class="rounded border-slate-300"></td>
                    <td>@if($image)<img src="{{ Storage::disk('public')->url($image->storage_key) }}" alt="" class="size-10 rounded-lg object-cover">@else<div class="grid size-10 place-items-center rounded-lg bg-slate-100 text-slate-400 dark:bg-slate-800"><flux:icon.photo class="size-5" /></div>@endif</td>
                    <td><a href="{{ url('/admin/products/'.$product->id.'/edit') }}" wire:navigate class="font-medium text-slate-950 hover:text-blue-700 dark:text-white dark:hover:text-blue-300">{{ $product->title }}</a><p class="mt-0.5 text-xs text-slate-500">{{ $product->variants_count }} {{ Str::plural('variant', $product->variants_count) }}</p></td>
                    <td><x-admin.status-badge :status="$product->status" /></td>
                    <td class="tabular-nums"><span class="{{ $inventory <= 0 ? 'text-red-600' : ($inventory < 10 ? 'text-amber-600' : '') }}">{{ $inventory }}</span></td>
                    <td>{{ $product->product_type ?: '—' }}</td><td>{{ $product->vendor ?: '—' }}</td><td class="whitespace-nowrap text-slate-500">{{ $product->updated_at->diffForHumans(short: true) }}</td>
                </tr>
            @empty<x-admin.table-empty colspan="8" title="No products match your filters" description="Try changing your search or filters." />@endforelse
            <x-slot:pagination>{{ $this->products->links() }}</x-slot:pagination>
        </x-admin.table-shell>
    @endif
</div>
