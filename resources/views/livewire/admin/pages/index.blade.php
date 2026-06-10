<div class="space-y-6">
    <x-admin.breadcrumbs :items="[['label' => __('Pages')]]" />

    <div class="flex flex-wrap items-center justify-between gap-3">
        <flux:heading size="xl" level="1">{{ __('Pages') }}</flux:heading>

        @can('create', \App\Models\Page::class)
            <flux:button variant="primary" icon="plus" :href="route('admin.pages.create')" wire:navigate data-test="add-page-button">
                {{ __('Add page') }}
            </flux:button>
        @endcan
    </div>

    @if (! $this->hasAnyPages)
        <x-admin.card class="flex flex-col items-center gap-3 py-16 text-center">
            <flux:icon name="document-text" class="size-12 text-zinc-300 dark:text-zinc-600" />
            <flux:heading size="lg">{{ __('Create your first page') }}</flux:heading>
            <flux:text>{{ __('Add content pages like About Us, FAQ, or Shipping policies.') }}</flux:text>
            @can('create', \App\Models\Page::class)
                <flux:button variant="primary" :href="route('admin.pages.create')" wire:navigate class="mt-2">
                    {{ __('Add page') }}
                </flux:button>
            @endcan
        </x-admin.card>
    @else
        <flux:input
            wire:model.live.debounce.300ms="search"
            icon="magnifying-glass"
            :placeholder="__('Search pages...')"
            class="max-w-xs"
            data-test="page-search"
        />

        <x-admin.card class="!p-0">
            <div class="overflow-x-auto" wire:loading.class="opacity-50">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 text-left text-xs font-semibold text-zinc-500 uppercase dark:border-zinc-700 dark:text-zinc-400">
                            <th class="px-4 py-2.5">{{ __('Title') }}</th>
                            <th class="px-4 py-2.5">{{ __('Handle') }}</th>
                            <th class="px-4 py-2.5">{{ __('Status') }}</th>
                            <th class="px-4 py-2.5">{{ __('Updated') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse ($this->pages as $page)
                            <tr wire:key="page-{{ $page->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.pages.edit', $page) }}" wire:navigate class="font-medium text-zinc-900 hover:underline dark:text-white">
                                        {{ $page->title }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">/pages/{{ $page->handle }}</td>
                                <td class="px-4 py-3"><x-admin.status-badge :status="$page->status" /></td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $page->updated_at?->diffForHumans(short: true) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-10 text-center">
                                    <flux:text>{{ __('No pages match your search.') }}</flux:text>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($this->pages->hasPages())
                <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                    {{ $this->pages->links() }}
                </div>
            @endif
        </x-admin.card>
    @endif
</div>
