<div>
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">{{ __('Search Settings') }}</flux:heading>
        <flux:button variant="primary" wire:click="reindex">
            {{ __('Reindex') }}
        </flux:button>
    </div>

    <div class="space-y-6">
        {{-- Synonyms --}}
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6">
            <flux:heading size="md" class="mb-4">{{ __('Synonyms') }}</flux:heading>
            <flux:text class="text-sm text-zinc-500 mb-4">{{ __('Add synonym groups so related terms return the same results (e.g. "shirt, tee, top").') }}</flux:text>

            <div class="flex gap-4 items-end mb-4">
                <div class="flex-1">
                    <flux:field>
                        <flux:label>{{ __('Synonym group') }}</flux:label>
                        <flux:input wire:model="newSynonym" placeholder="{{ __('shirt, tee, top') }}" />
                        <flux:error name="newSynonym" />
                    </flux:field>
                </div>
                <flux:button wire:click="addSynonym">{{ __('Add') }}</flux:button>
            </div>

            @if(count($synonyms) > 0)
                <div class="space-y-2">
                    @foreach($synonyms as $index => $synonym)
                        <div class="flex items-center justify-between rounded-md border border-zinc-200 dark:border-zinc-700 px-3 py-2">
                            <span class="text-sm">{{ $synonym }}</span>
                            <flux:button size="sm" variant="ghost" wire:click="removeSynonym({{ $index }})" icon="x-mark" />
                        </div>
                    @endforeach
                </div>
            @else
                <flux:text class="text-sm text-zinc-400">{{ __('No synonyms configured.') }}</flux:text>
            @endif
        </div>

        {{-- Stop Words --}}
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6">
            <flux:heading size="md" class="mb-4">{{ __('Stop Words') }}</flux:heading>
            <flux:text class="text-sm text-zinc-500 mb-4">{{ __('Stop words are excluded from search queries (e.g. "the", "and", "or").') }}</flux:text>

            <div class="flex gap-4 items-end mb-4">
                <div class="flex-1">
                    <flux:field>
                        <flux:label>{{ __('Stop word') }}</flux:label>
                        <flux:input wire:model="newStopWord" placeholder="{{ __('the') }}" />
                        <flux:error name="newStopWord" />
                    </flux:field>
                </div>
                <flux:button wire:click="addStopWord">{{ __('Add') }}</flux:button>
            </div>

            @if(count($stopWords) > 0)
                <div class="flex flex-wrap gap-2">
                    @foreach($stopWords as $index => $word)
                        <flux:badge color="zinc" class="flex items-center gap-1">
                            {{ $word }}
                            <button wire:click="removeStopWord({{ $index }})" class="ml-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </flux:badge>
                    @endforeach
                </div>
            @else
                <flux:text class="text-sm text-zinc-400">{{ __('No stop words configured.') }}</flux:text>
            @endif
        </div>
    </div>
</div>
