<div>
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">{{ __('Collections') }}</flux:heading>
        <flux:button variant="primary" :href="route('admin.collections.create')" wire:navigate>
            {{ __('Add collection') }}
        </flux:button>
    </div>

    <div class="mb-4">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search collections...') }}" icon="magnifying-glass" />
    </div>

    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 overflow-hidden">
        @if($this->collections->count() > 0)
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                        <th class="p-3 text-left font-medium text-zinc-500">{{ __('Title') }}</th>
                        <th class="p-3 text-left font-medium text-zinc-500">{{ __('Products') }}</th>
                        <th class="p-3 text-left font-medium text-zinc-500">{{ __('Status') }}</th>
                        <th class="p-3 text-left font-medium text-zinc-500">{{ __('Updated') }}</th>
                        <th class="p-3"></th>
                    </tr>
                </thead>
                <tbody wire:loading.class="opacity-50">
                    @foreach($this->collections as $collection)
                        <tr class="border-b border-zinc-100 dark:border-zinc-800">
                            <td class="p-3">
                                <a href="{{ route('admin.collections.edit', $collection) }}" class="text-accent hover:underline" wire:navigate>
                                    {{ $collection->title }}
                                </a>
                            </td>
                            <td class="p-3">{{ $collection->products_count }}</td>
                            <td class="p-3">
                                <flux:badge size="sm" :color="match($collection->status->value) {
                                    'active' => 'green', 'archived' => 'red', default => 'zinc',
                                }">{{ ucfirst($collection->status->value) }}</flux:badge>
                            </td>
                            <td class="p-3 text-zinc-500">{{ $collection->updated_at->diffForHumans() }}</td>
                            <td class="p-3">
                                <flux:button size="sm" variant="ghost" wire:click="deleteCollection({{ $collection->id }})" wire:confirm="{{ __('Are you sure?') }}" icon="trash" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="p-4">{{ $this->collections->links() }}</div>
        @else
            <div class="p-12 text-center">
                <flux:heading size="lg">{{ __('Create your first collection') }}</flux:heading>
                <flux:text class="mt-2 text-zinc-500">{{ __('Organize products into collections for your storefront.') }}</flux:text>
                <div class="mt-6">
                    <flux:button variant="primary" :href="route('admin.collections.create')" wire:navigate>{{ __('Add collection') }}</flux:button>
                </div>
            </div>
        @endif
    </div>
</div>
