<div>
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" wire:navigate>Home</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Pages</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl">Pages</flux:heading>
        @can('create', App\Models\Page::class)
            <flux:button variant="primary" href="{{ route('admin.pages.create') }}" wire:navigate>Create page</flux:button>
        @endcan
    </div>

    <div class="mb-4">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Search pages..." aria-label="Search pages" class="max-w-sm" />
    </div>

    <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900" wire:loading.class="opacity-50">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                    <th class="px-4 py-3 font-medium">Title</th>
                    <th class="px-4 py-3 font-medium">Handle</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium">Updated</th>
                    <th class="px-4 py-3 font-medium"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pages as $page)
                    <tr class="border-b border-zinc-100 dark:border-zinc-800" wire:key="page-{{ $page->getKey() }}">
                        <td class="px-4 py-3"><a href="{{ route('admin.pages.edit', $page) }}" class="font-medium underline" wire:navigate>{{ $page->title }}</a></td>
                        <td class="px-4 py-3">{{ $page->handle }}</td>
                        <td class="px-4 py-3"><flux:badge :color="$page->status->value === 'published' ? 'green' : 'zinc'">{{ ucfirst($page->status->value) }}</flux:badge></td>
                        <td class="px-4 py-3">{{ $page->updated_at?->diffForHumans() }}</td>
                        <td class="px-4 py-3 text-right">
                            @can('delete', $page)
                                <flux:button variant="ghost" size="sm" wire:click="confirmDelete({{ $page->getKey() }})" aria-label="Delete {{ $page->title }}">
                                    <flux:icon.trash class="size-4" variant="outline" />
                                </flux:button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center"><flux:text variant="subtle">No pages found.</flux:text></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $pages->links() }}
    </div>

    <flux:modal wire:model="showDeleteModal" class="max-w-md">
        <flux:heading size="lg">Delete this page?</flux:heading>
        <flux:text class="mt-2">The page will be permanently removed from your store.</flux:text>
        <div class="mt-4 flex justify-end gap-2">
            <flux:button variant="ghost" wire:click="$set('showDeleteModal', false)">Cancel</flux:button>
            <flux:button variant="danger" wire:click="deletePage">Delete</flux:button>
        </div>
    </flux:modal>
</div>
