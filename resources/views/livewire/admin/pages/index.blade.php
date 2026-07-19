<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl">Pages</flux:heading>

        @can('create', \App\Models\Page::class)
            <flux:button variant="primary" icon="plus" :href="route('admin.pages.create')" wire:navigate>Add page</flux:button>
        @endcan
    </div>

    @if (! $hasPages)
        <div class="flex flex-col items-center rounded-lg border border-zinc-200 bg-white px-6 py-16 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:icon name="document-text" class="size-12 text-zinc-300 dark:text-zinc-600" />
            <flux:heading size="lg" class="mt-4">Create your first page</flux:heading>
            <flux:text class="mt-1">Add content pages like About, Contact, or Policies.</flux:text>
            @can('create', \App\Models\Page::class)
                <flux:button variant="primary" class="mt-6" :href="route('admin.pages.create')" wire:navigate>Add page</flux:button>
            @endcan
        </div>
    @else
        <div class="flex flex-wrap items-center gap-3">
            <flux:input icon="magnifying-glass" wire:model.live.debounce.300ms="search" placeholder="Search pages..." class="w-full sm:w-72" aria-label="Search pages" />
        </div>

        <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <table class="w-full min-w-[640px] text-left text-sm" wire:loading.class="opacity-50">
                <thead>
                    <tr class="border-b border-zinc-200 text-xs tracking-wider text-zinc-500 uppercase dark:border-zinc-700 dark:text-zinc-400">
                        <th class="px-4 py-3 font-medium">Title</th>
                        <th class="px-4 py-3 font-medium">Handle</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">Updated</th>
                        <th class="px-4 py-3 font-medium"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($pages as $page)
                        <tr wire:key="page-{{ $page->id }}">
                            <td class="px-4 py-3">
                                @can('update', $page)
                                    <a href="{{ route('admin.pages.edit', $page) }}" wire:navigate class="font-medium text-zinc-900 hover:underline dark:text-zinc-100">
                                        {{ $page->title }}
                                    </a>
                                @else
                                    <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $page->title }}</span>
                                @endcan
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $page->handle }}</td>
                            <td class="px-4 py-3">
                                <flux:badge size="sm" :color="match ($page->status) {
                                    \App\Enums\PageStatus::Published => 'green',
                                    \App\Enums\PageStatus::Archived => 'red',
                                    default => 'zinc',
                                }">{{ ucfirst($page->status->value) }}</flux:badge>
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $page->updated_at->diffForHumans() }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    @can('update', $page)
                                        <flux:button size="sm" variant="ghost" icon="pencil" :href="route('admin.pages.edit', $page)" wire:navigate aria-label="Edit {{ $page->title }}" />
                                    @endcan
                                    @can('delete', $page)
                                        <flux:button size="sm" variant="ghost" icon="trash" wire:click="confirmDelete({{ $page->id }})" aria-label="Delete {{ $page->title }}" />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                No pages match your search.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $pages->links() }}
    @endif

    {{-- Delete confirmation modal (spec 03 §19.3) --}}
    <flux:modal wire:model="confirmingDelete" name="confirm-delete-page" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">Delete this page?</flux:heading>
            <flux:text>The page will be permanently removed from your store.</flux:text>
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('confirmingDelete', false)">Cancel</flux:button>
                <flux:button variant="danger" wire:click="delete">Delete</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
