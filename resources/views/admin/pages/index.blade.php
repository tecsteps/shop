<div class="space-y-6">
    <x-admin.page-header title="Pages" description="Publish the informational pages linked from your storefront."><x-slot:actions><flux:button href="{{ url('/admin/pages/create') }}" wire:navigate variant="primary" icon="plus">Create page</flux:button></x-slot:actions></x-admin.page-header>
    <x-admin.list-toolbar><x-slot:search><flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Search pages…" aria-label="Search pages" /></x-slot:search></x-admin.list-toolbar>
    @if($this->pages->isEmpty() && $search === '')<x-admin.empty-state title="Create your first page" description="Add policies, help, or brand stories to your storefront."><x-slot:action><flux:button href="{{ url('/admin/pages/create') }}" wire:navigate variant="primary">Create page</flux:button></x-slot:action></x-admin.empty-state>@else
        <x-admin.table-shell caption="Pages" loading-target="search"><x-slot:head><tr><th>Title</th><th>Handle</th><th>Status</th><th>Updated</th></tr></x-slot:head>
            @forelse($this->pages as $page)<tr wire:key="page-{{ $page->id }}"><td><a href="{{ url('/admin/pages/'.$page->id.'/edit') }}" wire:navigate class="font-medium text-zinc-950 hover:text-blue-700 dark:text-white">{{ $page->title }}</a></td><td class="font-mono text-xs text-zinc-500">/{{ $page->handle }}</td><td><x-admin.status-badge :status="$page->status" /></td><td class="whitespace-nowrap text-zinc-500">{{ $page->updated_at->diffForHumans(short: true) }}</td></tr>@empty<x-admin.table-empty colspan="4" title="No pages match your search" />@endforelse
            <x-slot:pagination>{{ $this->pages->links() }}</x-slot:pagination>
        </x-admin.table-shell>
    @endif
</div>
