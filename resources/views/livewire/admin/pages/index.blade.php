<div>
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">Pages</flux:heading>
        <flux:button variant="primary" :href="route('admin.pages.create')" wire:navigate>
            Add page
        </flux:button>
    </div>

    <div class="mb-4">
        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="Search pages..."
            icon="magnifying-glass"
        />
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                    <th class="pb-3 text-sm font-medium text-zinc-500 dark:text-zinc-400">Title</th>
                    <th class="pb-3 text-sm font-medium text-zinc-500 dark:text-zinc-400">Handle</th>
                    <th class="pb-3 text-sm font-medium text-zinc-500 dark:text-zinc-400">Status</th>
                    <th class="pb-3 text-sm font-medium text-zinc-500 dark:text-zinc-400">Updated</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700" wire:loading.class="opacity-50">
                @forelse ($this->pages as $page)
                    <tr wire:key="page-{{ $page->id }}">
                        <td class="py-3">
                            <a href="{{ route('admin.pages.edit', $page) }}" wire:navigate class="text-sm font-medium text-zinc-900 dark:text-zinc-100 hover:underline">
                                {{ $page->title }}
                            </a>
                        </td>
                        <td class="py-3 text-sm text-zinc-500 dark:text-zinc-400">{{ $page->handle }}</td>
                        <td class="py-3">
                            @php
                                $color = match($page->status->value) {
                                    'published' => 'green',
                                    'archived' => 'red',
                                    default => 'zinc',
                                };
                            @endphp
                            <flux:badge size="sm" :color="$color">{{ ucfirst($page->status->value) }}</flux:badge>
                        </td>
                        <td class="py-3 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $page->updated_at->diffForHumans() }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-12 text-center">
                            <flux:icon name="document-text" class="size-12 mx-auto text-zinc-400 dark:text-zinc-500 mb-4" />
                            <flux:heading size="lg">No pages yet</flux:heading>
                            <flux:text class="mt-1">Create your first page to add content to your store.</flux:text>
                            <div class="mt-4">
                                <flux:button variant="primary" :href="route('admin.pages.create')" wire:navigate>Add page</flux:button>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($this->pages->hasPages())
        <div class="mt-4">
            {{ $this->pages->links() }}
        </div>
    @endif
</div>
