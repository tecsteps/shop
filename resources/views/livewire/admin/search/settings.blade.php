<form wire:submit="save" class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <flux:heading size="xl">Search settings</flux:heading>
            <flux:text>Synonyms, stop words, and index maintenance.</flux:text>
            @if($lastIndexedAt)
                <flux:text class="mt-1">Last indexed {{ $lastIndexedAt }}.</flux:text>
            @endif
        </div>

        <div class="flex gap-2">
            <flux:button type="button" wire:click="reindex">Reindex</flux:button>
            <flux:button type="submit" variant="primary">Save settings</flux:button>
        </div>
    </div>

    <section class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <flux:textarea wire:model="synonymGroups" label="Synonym groups" rows="12" description="One comma-separated group per line." />
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <flux:textarea wire:model="stopWords" label="Stop words" rows="12" description="One word per line." />
        </div>
    </section>
</form>
