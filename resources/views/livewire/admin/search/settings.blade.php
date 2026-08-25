<div class="max-w-3xl">
    <flux:heading size="xl">Search Settings</flux:heading>

    <div class="mt-6 space-y-6">
        {{-- Synonyms --}}
        <section>
            <flux:heading size="lg">Synonyms</flux:heading>
            <flux:text class="mt-1">Define groups of words that should be treated as equivalent.</flux:text>

            <div class="mt-4 space-y-2">
                @foreach ($synonymGroups as $index => $group)
                    <div class="flex items-center gap-2">
                        <flux:input
                            wire:model="synonymGroups.{{ $index }}"
                            placeholder="t-shirt, tee, tshirt"
                            class="flex-1"
                        />
                        <flux:button variant="ghost" size="sm" icon="trash" wire:click="removeSynonymGroup({{ $index }})" aria-label="Remove synonym group" />
                    </div>
                @endforeach
            </div>

            <flux:button variant="ghost" size="sm" icon="plus" wire:click="addSynonymGroup" class="mt-3">
                Add synonym group
            </flux:button>
        </section>

        <flux:separator />

        {{-- Stop words --}}
        <section>
            <flux:heading size="lg">Stop words</flux:heading>
            <flux:text class="mt-1">Words that are excluded from search indexing.</flux:text>

            <flux:textarea wire:model="stopWords" rows="4" placeholder="the, a, an, is, are..." class="mt-3" />
            <flux:description>Separate words with commas.</flux:description>
        </section>

        <flux:separator />

        {{-- Search index --}}
        <section>
            <flux:heading size="lg">Search index</flux:heading>

            <div class="mt-4 flex items-center gap-3">
                <flux:button variant="primary" wire:click="triggerReindex" :disabled="$isReindexing">
                    {{ $isReindexing ? 'Reindexing...' : 'Reindex now' }}
                </flux:button>

                @if ($lastIndexedAt)
                    <flux:text>Last indexed: {{ $lastIndexedAt }}</flux:text>
                @endif
            </div>

            @if ($isReindexing)
                <div class="mt-4" wire:poll.2s="pollReindexStatus">
                    <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                        <div
                            class="h-full rounded-full bg-zinc-800 transition-all dark:bg-zinc-200"
                            style="width: {{ $reindexProgress }}%"
                        ></div>
                    </div>
                    <flux:text class="mt-1">{{ $reindexProgress }}% complete</flux:text>
                </div>
            @endif
        </section>

        <div class="flex justify-end">
            <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled">Save</flux:button>
        </div>
    </div>
</div>
