<div>
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">{{ __('Pages') }}</flux:heading>
        <flux:button variant="primary" :href="route('admin.pages.create')" wire:navigate>
            {{ __('Add page') }}
        </flux:button>
    </div>

    <div class="mb-4">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search pages...') }}" icon="magnifying-glass" />
    </div>

    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 overflow-hidden">
        @if($this->pages->count() > 0)
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                        <th class="p-3 text-left font-medium text-zinc-500">{{ __('Title') }}</th>
                        <th class="p-3 text-left font-medium text-zinc-500">{{ __('Status') }}</th>
                        <th class="p-3 text-left font-medium text-zinc-500">{{ __('Updated') }}</th>
                        <th class="p-3"></th>
                    </tr>
                </thead>
                <tbody wire:loading.class="opacity-50">
                    @foreach($this->pages as $page)
                        <tr class="border-b border-zinc-100 dark:border-zinc-800">
                            <td class="p-3">
                                <a href="{{ route('admin.pages.edit', $page) }}" class="text-accent hover:underline" wire:navigate>
                                    {{ $page->title }}
                                </a>
                            </td>
                            <td class="p-3">
                                <flux:badge size="sm" :color="match($page->status->value) {
                                    'published' => 'green', 'draft' => 'zinc', 'archived' => 'red',
                                }">{{ ucfirst($page->status->value) }}</flux:badge>
                            </td>
                            <td class="p-3 text-zinc-500">{{ $page->updated_at->diffForHumans() }}</td>
                            <td class="p-3">
                                <flux:button size="sm" variant="ghost" wire:click="deletePage({{ $page->id }})" wire:confirm="{{ __('Delete this page?') }}" icon="trash" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="p-4">{{ $this->pages->links() }}</div>
        @else
            <div class="p-12 text-center">
                <flux:icon name="document-text" class="mx-auto h-12 w-12 text-zinc-400" />
                <flux:heading size="lg" class="mt-4">{{ __('No pages yet') }}</flux:heading>
                <flux:text class="mt-2 text-zinc-500">{{ __('Create content pages for your storefront.') }}</flux:text>
                <div class="mt-6">
                    <flux:button variant="primary" :href="route('admin.pages.create')" wire:navigate>{{ __('Add page') }}</flux:button>
                </div>
            </div>
        @endif
    </div>
</div>
