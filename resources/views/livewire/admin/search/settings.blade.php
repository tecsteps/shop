<div>
    <x-admin.breadcrumbs :items="[['label' => __('Search settings')]]" />

    <flux:heading size="xl" level="1" class="mb-6">{{ __('Search settings') }}</flux:heading>

    <form wire:submit="save" class="max-w-2xl space-y-6">
        <x-admin.card title="{{ __('Synonyms') }}">
            <flux:text class="mb-3 text-sm">{{ __('Define groups of words that should be treated as equivalent.') }}</flux:text>
            <div class="space-y-2">
                @foreach ($synonymGroups as $index => $group)
                    <div class="flex items-center gap-2" wire:key="syn-{{ $index }}">
                        <flux:input wire:model="synonymGroups.{{ $index }}" placeholder="t-shirt, tee, tshirt" class="flex-1" />
                        <flux:button type="button" variant="ghost" icon="trash" wire:click="removeSynonymGroup({{ $index }})" :aria-label="__('Remove group')" />
                    </div>
                @endforeach
            </div>
            <div class="mt-3">
                <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addSynonymGroup">{{ __('Add synonym group') }}</flux:button>
            </div>
        </x-admin.card>

        <x-admin.card title="{{ __('Stop words') }}">
            <flux:text class="mb-3 text-sm">{{ __('Words that are excluded from search indexing.') }}</flux:text>
            <flux:field>
                <flux:textarea wire:model="stopWords" rows="4" placeholder="the, a, an, is, are..." />
                <flux:description>{{ __('Separate words with commas.') }}</flux:description>
            </flux:field>
        </x-admin.card>

        <x-admin.card title="{{ __('Search index') }}">
            <div class="flex flex-wrap items-center gap-4">
                <flux:button type="button" wire:click="triggerReindex" wire:loading.attr="disabled" wire:target="triggerReindex" data-test="reindex">
                    <span wire:loading.remove wire:target="triggerReindex">{{ __('Reindex now') }}</span>
                    <span wire:loading wire:target="triggerReindex">{{ __('Reindexing...') }}</span>
                </flux:button>
                @if ($lastIndexedAt)
                    <flux:text class="text-sm">{{ __('Last indexed:') }} {{ $lastIndexedAt }} ({{ $lastIndexedCount }} {{ __('products') }})</flux:text>
                @endif
            </div>
        </x-admin.card>

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary" data-test="save-search">{{ __('Save') }}</flux:button>
        </div>
    </form>
</div>
