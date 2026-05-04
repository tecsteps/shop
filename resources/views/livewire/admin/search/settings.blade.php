<div class="space-y-8 p-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">Search settings</flux:heading>
            <flux:text class="mt-2">Tune storefront search behavior and rebuild the local SQLite index.</flux:text>
        </div>

        <flux:button wire:click="triggerReindex" icon="arrow-path" variant="ghost">
            Reindex now
        </flux:button>
    </div>

    @if (session('status'))
        <flux:callout color="green" icon="check-circle">
            {{ session('status') }}
        </flux:callout>
    @endif

    <form wire:submit="save" class="space-y-8">
        <section class="space-y-4">
            <div>
                <flux:heading size="lg">Synonyms</flux:heading>
                <flux:text>Group equivalent search terms with comma-separated words.</flux:text>
            </div>

            <div class="space-y-3">
                @foreach ($synonymGroups as $index => $group)
                    <div class="flex items-start gap-2" wire:key="synonym-group-{{ $index }}">
                        <flux:input wire:model="synonymGroups.{{ $index }}" placeholder="t-shirt, tee, tshirt" aria-label="Synonym group {{ $index + 1 }}" />
                        <flux:button wire:click="removeSynonymGroup({{ $index }})" type="button" icon="trash" variant="ghost" aria-label="Remove synonym group {{ $index + 1 }}" />
                    </div>
                    <flux:error name="synonymGroups.{{ $index }}" />
                @endforeach
            </div>

            <flux:button wire:click="addSynonymGroup" type="button" icon="plus" variant="ghost">
                Add synonym group
            </flux:button>
        </section>

        <flux:separator />

        <section class="space-y-4">
            <div>
                <flux:heading size="lg">Stop words</flux:heading>
                <flux:text>Separate excluded words with commas.</flux:text>
            </div>

            <flux:textarea wire:model="stopWords" rows="5" placeholder="the, a, an, is, are" />
            <flux:error name="stopWords" />
        </section>

        <flux:separator />

        <section class="space-y-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <flux:heading size="lg">Search index</flux:heading>
                    <flux:text>Last indexed: {{ $lastIndexedAt ?? 'Never' }}</flux:text>
                </div>

                @if ($reindexProgress !== null)
                    <flux:badge color="blue">{{ $reindexProgress }}% complete</flux:badge>
                @endif
            </div>
        </section>

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary">
                Save
            </flux:button>
        </div>
    </form>
</div>
