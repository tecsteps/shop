<div class="space-y-6">
    <x-admin.page-header title="Collections" description="Group products into curated storefront destinations."><x-slot:actions>@if($adminRole !== 'support')<flux:button href="{{ url('/admin/collections/create') }}" wire:navigate variant="primary" icon="plus">Add collection</flux:button>@endif</x-slot:actions></x-admin.page-header>
    <x-admin.list-toolbar><x-slot:search><flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Search collections…" aria-label="Search collections" /></x-slot:search><x-slot:filters><flux:select wire:model.live="statusFilter" aria-label="Filter collection status"><flux:select.option value="all">All statuses</flux:select.option><flux:select.option value="active">Active</flux:select.option><flux:select.option value="archived">Archived</flux:select.option></flux:select></x-slot:filters></x-admin.list-toolbar>
    @if($this->collections->isEmpty() && $search === '' && $statusFilter === 'all')
        <x-admin.empty-state title="Create your first collection" description="Organize products so customers can browse related items."><x-slot:action><flux:button href="{{ url('/admin/collections/create') }}" wire:navigate variant="primary">Add collection</flux:button></x-slot:action></x-admin.empty-state>
    @else
        <x-admin.table-shell caption="Collections" loading-target="search,statusFilter">
            <x-slot:head><tr><th>Title</th><th>Products</th><th>Status</th><th>Updated</th><th><span class="sr-only">Actions</span></th></tr></x-slot:head>
            @forelse($this->collections as $collection)<tr wire:key="collection-{{ $collection->id }}"><td>@if($adminRole === 'support')<span class="font-medium text-slate-950 dark:text-white">{{ $collection->title }}</span>@else<a href="{{ url('/admin/collections/'.$collection->id.'/edit') }}" wire:navigate class="font-medium text-slate-950 hover:text-blue-700 dark:text-white">{{ $collection->title }}</a>@endif<p class="text-xs text-slate-500">/{{ $collection->handle }}</p></td><td>{{ $collection->products_count }}</td><td><x-admin.status-badge :status="$collection->status" /></td><td class="whitespace-nowrap text-slate-500">{{ $collection->updated_at->diffForHumans(short: true) }}</td><td class="text-right">@if(in_array($adminRole, ['owner','admin']))<flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="deleteCollection({{ $collection->id }})" wire:confirm="Delete this collection?" aria-label="Delete {{ $collection->title }}" />@endif</td></tr>
            @empty<x-admin.table-empty colspan="5" title="No collections match your filters" />@endforelse
            <x-slot:pagination>{{ $this->collections->links() }}</x-slot:pagination>
        </x-admin.table-shell>
    @endif
</div>
