<div class="space-y-6">
    <x-admin.breadcrumbs :items="[['label' => __('Collections')]]" />

    <div class="flex flex-wrap items-center justify-between gap-3">
        <flux:heading size="xl" level="1">{{ __('Collections') }}</flux:heading>

        @can('create', \App\Models\Collection::class)
            <flux:button variant="primary" icon="plus" :href="route('admin.collections.create')" wire:navigate data-test="add-collection-button">
                {{ __('Add collection') }}
            </flux:button>
        @endcan
    </div>

    @if (! $this->hasAnyCollections)
        <x-admin.card class="flex flex-col items-center gap-3 py-16 text-center">
            <flux:icon name="rectangle-stack" class="size-12 text-zinc-300 dark:text-zinc-600" />
            <flux:heading size="lg">{{ __('Create your first collection') }}</flux:heading>
            <flux:text>{{ __('Group products into collections to organize your storefront.') }}</flux:text>
            @can('create', \App\Models\Collection::class)
                <flux:button variant="primary" :href="route('admin.collections.create')" wire:navigate class="mt-2">
                    {{ __('Add collection') }}
                </flux:button>
            @endcan
        </x-admin.card>
    @else
        <div class="flex flex-wrap items-center gap-3">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                :placeholder="__('Search collections...')"
                class="max-w-xs"
                data-test="collection-search"
            />

            <flux:select wire:model.live="statusFilter" size="sm" class="max-w-44">
                <flux:select.option value="all">{{ __('Status: All') }}</flux:select.option>
                <flux:select.option value="draft">{{ __('Draft') }}</flux:select.option>
                <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
                <flux:select.option value="archived">{{ __('Archived') }}</flux:select.option>
            </flux:select>
        </div>

        <x-admin.card class="!p-0">
            <div class="overflow-x-auto" wire:loading.class="opacity-50">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 text-left text-xs font-semibold text-zinc-500 uppercase dark:border-zinc-700 dark:text-zinc-400">
                            <th class="px-4 py-2.5">{{ __('Title') }}</th>
                            <th class="px-4 py-2.5">{{ __('Products') }}</th>
                            <th class="px-4 py-2.5">{{ __('Status') }}</th>
                            <th class="px-4 py-2.5">{{ __('Updated') }}</th>
                            <th class="w-12 px-4 py-2.5"><span class="sr-only">{{ __('Actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse ($this->collections as $collection)
                            <tr wire:key="collection-{{ $collection->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.collections.edit', $collection) }}" wire:navigate class="font-medium text-zinc-900 hover:underline dark:text-white">
                                        {{ $collection->title }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ number_format($collection->products_count) }}</td>
                                <td class="px-4 py-3"><x-admin.status-badge :status="$collection->status" /></td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $collection->updated_at?->diffForHumans(short: true) }}</td>
                                <td class="px-4 py-3 text-right">
                                    @can('delete', $collection)
                                        <flux:button
                                            variant="ghost"
                                            size="sm"
                                            icon="trash"
                                            wire:click="confirmDelete({{ $collection->id }})"
                                            aria-label="{{ __('Delete :title', ['title' => $collection->title]) }}"
                                            data-test="delete-collection-{{ $collection->id }}"
                                        />
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10 text-center">
                                    <flux:text>{{ __('No collections match your filters.') }}</flux:text>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($this->collections->hasPages())
                <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                    {{ $this->collections->links() }}
                </div>
            @endif
        </x-admin.card>

        <flux:modal name="confirm-delete-collection" class="md:max-w-md">
            <div class="space-y-4">
                <flux:heading size="lg">{{ __('Delete this collection?') }}</flux:heading>
                <flux:text>
                    {{ __('The collection will be removed. Products in the collection are not deleted.') }}
                </flux:text>
                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button variant="danger" wire:click="deleteCollection" data-test="confirm-delete-collection-button">
                        {{ __('Delete collection') }}
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
