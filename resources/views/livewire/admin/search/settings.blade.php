<div>
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" wire:navigate>Home</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Search Settings</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:heading size="xl" class="mb-6">Search Settings</flux:heading>

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-4">
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">Synonyms</flux:heading>
            <flux:text variant="subtle" class="mt-1 text-sm">Define groups of words that should be treated as equivalent.</flux:text>
            <div class="mt-4 flex flex-col gap-2">
                @foreach ($this->synonymGroups as $index => $group)
                    <div class="flex items-center gap-2" wire:key="synonym-{{ $index }}">
                        <flux:input wire:model="synonymGroups.{{ $index }}" placeholder="t-shirt, tee, tshirt" aria-label="Synonym group" class="flex-1" />
                        <flux:button variant="ghost" size="sm" wire:click="removeSynonymGroup({{ $index }})" aria-label="Remove synonym group">
                            <flux:icon.trash class="size-4" variant="outline" />
                        </flux:button>
                    </div>
                @endforeach
            </div>
            <flux:button variant="ghost" size="sm" wire:click="addSynonymGroup" class="mt-3">
                <flux:icon.plus class="size-4" variant="outline" /> Add synonym group
            </flux:button>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:separator class="mb-4" />
            <flux:heading size="lg">Stop words</flux:heading>
            <flux:text variant="subtle" class="mt-1 text-sm">Words that are excluded from search indexing.</flux:text>
            <flux:textarea wire:model="stopWords" rows="4" placeholder="the, a, an, is, are..." class="mt-4" aria-label="Stop words" />
            <flux:text variant="subtle" class="mt-1 text-xs">Separate words with commas.</flux:text>
        </div>

        <div
            class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900"
            @if ($this->isReindexing) wire:poll.2s="pollReindexStatus" @endif
        >
            <flux:separator class="mb-4" />
            <flux:heading size="lg">Search index</flux:heading>
            <div class="mt-4 flex flex-wrap items-center gap-3">
                <flux:button variant="primary" wire:click="triggerReindex" :disabled="$this->isReindexing">
                    {{ $this->isReindexing ? 'Reindexing...' : 'Reindex now' }}
                </flux:button>
                <flux:text variant="subtle" class="text-sm">{{ $documentCount }} documents indexed</flux:text>
            </div>
            @if ($this->isReindexing)
                <div class="mt-4 h-2 rounded bg-zinc-200 dark:bg-zinc-700">
                    <div class="h-2 rounded bg-zinc-900 transition-all dark:bg-white" style="width: {{ $this->reindexProgress ?? 0 }}%"></div>
                </div>
                <flux:text variant="subtle" class="mt-1 text-xs">{{ $this->reindexProgress ?? 0 }}% complete</flux:text>
            @endif
        </div>

        <div class="flex justify-end">
            <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">Save</span>
                <span wire:loading wire:target="save">Saving...</span>
            </flux:button>
        </div>
    </div>
</div>
