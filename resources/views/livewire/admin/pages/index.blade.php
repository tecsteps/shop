<div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <flux:heading size="xl">Pages</flux:heading>
            <flux:text>Storefront content pages and publishing status.</flux:text>
        </div>

        <flux:button variant="primary" icon="plus" :href="route('admin.pages.create')" wire:navigate>Add page</flux:button>
    </div>

    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <div class="border-b border-zinc-200 p-4 dark:border-zinc-800">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search pages" icon="magnifying-glass" />
        </div>

        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
            @forelse ($pages as $page)
                <div wire:key="admin-page-{{ $page->id }}" class="grid gap-4 p-5 sm:grid-cols-[1fr_auto]">
                    <div>
                        <a href="{{ route('admin.pages.edit', $page) }}" wire:navigate class="font-medium hover:underline">{{ $page->title }}</a>
                        <div class="text-sm text-zinc-500">{{ $page->handle }}</div>
                    </div>
                    <flux:badge>{{ $page->status->value }}</flux:badge>
                </div>
            @empty
                <div class="p-10 text-sm text-zinc-500">No pages match the current filters.</div>
            @endforelse
        </div>

        <div class="border-t border-zinc-200 p-4 dark:border-zinc-800">{{ $pages->links() }}</div>
    </div>
</div>
