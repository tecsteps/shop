<section class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">Pages</flux:heading>
            <flux:text class="mt-1">Static storefront content pages.</flux:text>
        </div>

        @can('create', App\Models\Page::class)
            <flux:button :href="route('admin.pages.create')" wire:navigate variant="primary" icon="plus">
                Create page
            </flux:button>
        @endcan
    </div>

    <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Search pages..." aria-label="Search pages" />

    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">Title</th>
                        <th class="px-4 py-3">Handle</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Updated</th>
                    </tr>
                </thead>
                <tbody wire:loading.class="opacity-50" class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($pages as $page)
                        <tr wire:key="admin-page-{{ $page->getKey() }}">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.pages.edit', $page) }}" class="font-medium text-zinc-950 hover:underline dark:text-white" wire:navigate>
                                    {{ $page->title }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-zinc-500">/{{ $page->handle }}</td>
                            <td class="px-4 py-3">
                                <flux:badge :color="$this->statusColor($page->status)">{{ Str::headline($page->status->value) }}</flux:badge>
                            </td>
                            <td class="px-4 py-3 text-zinc-500">{{ $page->updated_at?->format('M j, Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-16 text-center text-zinc-500">No pages found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $pages->links() }}
</section>
