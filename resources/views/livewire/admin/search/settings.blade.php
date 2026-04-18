<div class="space-y-4">
    <flux:heading size="xl">Search</flux:heading>
    @if (session('success'))
        <flux:callout variant="success">{{ session('success') }}</flux:callout>
    @endif
    @if (session('error'))
        <flux:callout variant="danger">{{ session('error') }}</flux:callout>
    @endif
    <form wire:submit="save" class="space-y-3 rounded-xl bg-white p-4 shadow-sm dark:bg-zinc-900">
        <flux:input wire:model="synonyms" label="Synonyms (comma-separated)" />
        <flux:input wire:model="stopWords" label="Stop words (comma-separated)" />
        <flux:button type="submit" variant="primary">Save</flux:button>
    </form>
    <div class="rounded-xl bg-white p-4 shadow-sm dark:bg-zinc-900">
        <flux:heading size="sm">Reindex</flux:heading>
        <flux:text class="text-zinc-500">Rebuild the FTS index for all products.</flux:text>
        <flux:button class="mt-3" wire:click="reindex">Reindex all products</flux:button>
        @if ($reindexed > 0)
            <flux:text class="mt-2">Reindexed {{ $reindexed }} products.</flux:text>
        @endif
    </div>
</div>
