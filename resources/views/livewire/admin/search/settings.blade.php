<div class="space-y-6">
    <flux:heading size="xl">Search Settings</flux:heading>

    {{-- Synonyms (spec 03 §18) --}}
    <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">Synonyms</flux:heading>
        <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Define groups of words that should be treated as equivalent.</flux:text>

        <div class="mt-4 space-y-3">
            @foreach ($synonymGroups as $index => $group)
                <div class="flex items-center gap-2" wire:key="synonym-group-{{ $index }}">
                    <div class="flex-1">
                        <flux:input wire:model.blur="synonymGroups.{{ $index }}" placeholder="t-shirt, tee, tshirt" />
                    </div>
                    <flux:button variant="ghost" icon="trash" wire:click="removeSynonymGroup({{ $index }})" aria-label="Remove synonym group" />
                </div>
            @endforeach

            <flux:button variant="ghost" icon="plus" wire:click="addSynonymGroup">Add synonym group</flux:button>
        </div>
    </div>

    {{-- Stop words --}}
    <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">Stop words</flux:heading>
        <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Words that are excluded from search.</flux:text>

        <flux:field class="mt-4">
            <flux:textarea id="stopWords" wire:model.blur="stopWords" rows="4" placeholder="the, a, an, is, are..." />
            <flux:description>Separate words with commas.</flux:description>
        </flux:field>
    </div>

    {{-- Search index --}}
    <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">Search index</flux:heading>

        <div class="mt-4 flex items-center gap-4">
            <flux:button variant="primary" wire:click="triggerReindex" wire:loading.attr="disabled" wire:target="triggerReindex">
                <span wire:loading.remove wire:target="triggerReindex">Reindex now</span>
                <span wire:loading wire:target="triggerReindex">Reindexing...</span>
            </flux:button>
            @if ($lastIndexedAt !== null)
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Last indexed: {{ $lastIndexedAt }}</flux:text>
            @endif
        </div>
    </div>

    {{-- Recent search queries (spec 05 §16.4) --}}
    <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">Recent search queries</flux:heading>

        @if ($recentQueries->isEmpty())
            <flux:text class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">No searches recorded yet.</flux:text>
        @else
            <table class="mt-4 w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                        <th class="py-2 pr-4">Query</th>
                        <th class="py-2 pr-4">Results</th>
                        <th class="py-2">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recentQueries as $recentQuery)
                        <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800" wire:key="search-query-{{ $recentQuery->id }}">
                            <td class="py-2 pr-4 text-zinc-900 dark:text-zinc-100">{{ $recentQuery->query }}</td>
                            <td class="py-2 pr-4 text-zinc-600 dark:text-zinc-300">{{ $recentQuery->results_count }}</td>
                            <td class="py-2 text-zinc-600 dark:text-zinc-300">{{ $recentQuery->created_at?->toDayDateTimeString() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="flex justify-end">
        <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled" wire:target="save">
            <span wire:loading.remove wire:target="save">Save</span>
            <span wire:loading wire:target="save">Saving...</span>
        </flux:button>
    </div>
</div>
