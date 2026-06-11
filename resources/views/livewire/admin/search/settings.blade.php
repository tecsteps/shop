<div class="space-y-6">
    <x-admin.breadcrumbs :items="[['label' => __('Search settings')]]" />

    <flux:heading size="xl" level="1">{{ __('Search settings') }}</flux:heading>

    <form wire:submit="save" class="space-y-6">
        {{-- Synonyms --}}
        <x-admin.card class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('Synonyms') }}</flux:heading>
                <flux:text class="mt-1 text-sm">
                    {{ __('Define groups of words that should be treated as equivalent.') }}
                </flux:text>
            </div>

            <div class="space-y-3">
                @foreach ($synonymGroups as $index => $group)
                    <div class="flex items-center gap-2" wire:key="synonym-group-{{ $index }}">
                        <flux:input
                            wire:model="synonymGroups.{{ $index }}"
                            placeholder="{{ __('t-shirt, tee, tshirt') }}"
                            data-test="synonym-group-input-{{ $index }}"
                        />
                        <flux:button
                            variant="ghost"
                            size="sm"
                            icon="trash"
                            wire:click="removeSynonymGroup({{ $index }})"
                            aria-label="{{ __('Remove synonym group') }}"
                            data-test="remove-synonym-group-{{ $index }}"
                        />
                    </div>
                    <flux:error name="synonymGroups.{{ $index }}" />
                @endforeach
            </div>

            <flux:button variant="ghost" size="sm" icon="plus" wire:click="addSynonymGroup" data-test="add-synonym-group">
                {{ __('Add synonym group') }}
            </flux:button>
        </x-admin.card>

        {{-- Stop words --}}
        <x-admin.card class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('Stop words') }}</flux:heading>
                <flux:text class="mt-1 text-sm">
                    {{ __('Words that are excluded from search queries.') }}
                </flux:text>
            </div>

            <flux:field>
                <flux:textarea
                    wire:model="stopWords"
                    rows="4"
                    placeholder="{{ __('the, a, an, is, are...') }}"
                    data-test="stop-words-input"
                />
                <flux:description>{{ __('Separate words with commas.') }}</flux:description>
                <flux:error name="stopWords" />
            </flux:field>
        </x-admin.card>

        {{-- Search index --}}
        <x-admin.card class="space-y-4">
            <flux:heading size="lg">{{ __('Search index') }}</flux:heading>

            <div class="flex flex-wrap items-center gap-3">
                <flux:button
                    type="button"
                    variant="outline"
                    wire:click="triggerReindex"
                    wire:loading.attr="disabled"
                    wire:target="triggerReindex"
                    data-test="reindex-button"
                >
                    <span wire:loading.remove wire:target="triggerReindex">{{ __('Reindex now') }}</span>
                    <span wire:loading wire:target="triggerReindex">{{ __('Reindexing...') }}</span>
                </flux:button>

                @if ($lastIndexedAt !== null)
                    <flux:text class="text-sm" data-test="last-indexed-at">
                        {{ __('Last indexed: :timestamp', ['timestamp' => \Illuminate\Support\Carbon::parse($lastIndexedAt)->format('M j, Y g:i A')]) }}
                    </flux:text>
                @endif
            </div>
        </x-admin.card>

        <div>
            <flux:button type="submit" variant="primary" data-test="save-search-settings">
                {{ __('Save') }}
            </flux:button>
        </div>
    </form>
</div>
