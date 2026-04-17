<div>
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">Search Settings</flux:heading>
        <div class="flex items-center gap-3">
            <flux:button wire:click="reindex" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="reindex">Reindex Products</span>
                <span wire:loading wire:target="reindex">Reindexing...</span>
            </flux:button>
            <flux:button wire:click="save" variant="primary">Save</flux:button>
        </div>
    </div>

    @if ($reindexMessage)
        <div class="mb-6 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 rounded-lg px-4 py-3 text-sm">
            {{ $reindexMessage }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Synonyms --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <flux:heading size="lg" class="mb-2">Synonyms</flux:heading>
            <flux:text class="mb-4">Enter synonym groups, one per line. Words on the same line will be treated as equivalent in search.</flux:text>
            <flux:textarea
                wire:model="synonymsText"
                rows="10"
                placeholder="shirt, tee, t-shirt&#10;pants, trousers&#10;sneakers, trainers, shoes"
            />
        </div>

        {{-- Stop Words --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <flux:heading size="lg" class="mb-2">Stop Words</flux:heading>
            <flux:text class="mb-4">Enter words to exclude from search, one per line. These common words will be ignored during indexing.</flux:text>
            <flux:textarea
                wire:model="stopWordsText"
                rows="10"
                placeholder="the&#10;a&#10;an&#10;and&#10;or"
            />
        </div>
    </div>
</div>
