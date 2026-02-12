<div>
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" wire:navigate>Home</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Pages</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">Pages</flux:heading>
        <flux:button href="{{ route('admin.pages.create') }}" wire:navigate variant="primary" icon="plus">
            Create page
        </flux:button>
    </div>

    {{-- Filters --}}
    <div class="flex flex-wrap gap-4 mb-6">
        <div class="flex-1 min-w-[200px]">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search pages..." icon="magnifying-glass" />
        </div>
        <flux:select wire:model.live="statusFilter" class="w-40">
            <option value="">All statuses</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}">{{ ucfirst($status->value) }}</option>
            @endforeach
        </flux:select>
    </div>

    {{-- Table --}}
    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800">
                    <th class="px-4 py-3 text-left font-medium text-zinc-500 dark:text-zinc-400">Title</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-500 dark:text-zinc-400">Status</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-500 dark:text-zinc-400">Last updated</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-500 dark:text-zinc-400">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pages as $page)
                    <tr class="border-b border-zinc-100 dark:border-zinc-800" wire:key="page-{{ $page->id }}">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.pages.edit', $page) }}" wire:navigate class="font-medium text-zinc-900 dark:text-zinc-100 hover:underline">
                                {{ $page->title }}
                            </a>
                            <div class="text-xs text-zinc-500">/pages/{{ $page->handle }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <flux:badge size="sm" :color="match($page->status->value) { 'published' => 'green', 'draft' => 'yellow', 'archived' => 'zinc', default => 'zinc' }">
                                {{ ucfirst($page->status->value) }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                            {{ $page->updated_at->diffForHumans() }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <flux:button href="{{ route('admin.pages.edit', $page) }}" wire:navigate size="sm" variant="ghost" icon="pencil-square" />
                                <flux:button wire:click="delete({{ $page->id }})" wire:confirm="Are you sure you want to delete this page?" size="sm" variant="ghost" icon="trash" />
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">
                            No pages found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $pages->links() }}
    </div>
</div>
