<div class="space-y-4">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Pages</flux:heading>
        <flux:button href="{{ route('admin.pages.create') }}" variant="primary" wire:navigate>New page</flux:button>
    </div>
    <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-zinc-900">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 text-left text-zinc-500 dark:bg-zinc-800">
                <tr>
                    <th class="p-3">Title</th>
                    <th class="p-3">Handle</th>
                    <th class="p-3">Status</th>
                    <th class="p-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pages as $page)
                    <tr wire:key="page-{{ $page->id }}" class="border-t border-zinc-100 dark:border-zinc-800">
                        <td class="p-3"><a class="text-sky-600 hover:underline" href="{{ route('admin.pages.edit', $page) }}" wire:navigate>{{ $page->title }}</a></td>
                        <td class="p-3">{{ $page->handle }}</td>
                        <td class="p-3">{{ $page->status?->value }}</td>
                        <td class="p-3 text-right">
                            <flux:button size="xs" variant="danger" wire:click="delete({{ $page->id }})" wire:confirm="Delete this page?">Delete</flux:button>
                        </td>
                    </tr>
                @empty
                    <tr><td class="p-4 text-zinc-500" colspan="4">No pages yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $pages->links() }}</div>
</div>
