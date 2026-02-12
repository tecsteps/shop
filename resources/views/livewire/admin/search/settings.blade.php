<div>
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" wire:navigate>Home</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Search Settings</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:heading size="xl" class="mb-6">Search settings</flux:heading>

    <form wire:submit="save" class="space-y-6 max-w-2xl">
        {{-- Synonyms --}}
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900 space-y-4">
            <flux:heading size="lg">Synonyms</flux:heading>
            <flux:text class="text-zinc-500">Define synonym groups so searching for one term also matches its synonyms.</flux:text>
            <flux:field>
                <flux:label>Synonyms (JSON)</flux:label>
                <flux:textarea wire:model="synonymsJson" rows="6" class="font-mono text-sm" />
                <flux:description>Array of synonym groups, e.g. [["shirt", "tee", "top"], ["pants", "trousers"]]</flux:description>
                <flux:error name="synonymsJson" />
            </flux:field>
        </div>

        {{-- Stop Words --}}
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900 space-y-4">
            <flux:heading size="lg">Stop words</flux:heading>
            <flux:text class="text-zinc-500">Words to ignore during search.</flux:text>
            <flux:field>
                <flux:label>Stop words (JSON)</flux:label>
                <flux:textarea wire:model="stopWordsJson" rows="4" class="font-mono text-sm" />
                <flux:description>Array of words to ignore, e.g. ["the", "a", "an", "and"]</flux:description>
                <flux:error name="stopWordsJson" />
            </flux:field>
        </div>

        {{-- Boost Fields --}}
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900 space-y-4">
            <flux:heading size="lg">Boost fields</flux:heading>
            <flux:text class="text-zinc-500">Prioritize certain fields in search results.</flux:text>
            <flux:field>
                <flux:label>Boost configuration (JSON)</flux:label>
                <flux:textarea wire:model="boostFieldsJson" rows="6" class="font-mono text-sm" />
                <flux:description>Object with field names and boost weights, e.g. {"title": 3, "description": 1, "tags": 2}</flux:description>
                <flux:error name="boostFieldsJson" />
            </flux:field>
        </div>

        <flux:button type="submit" variant="primary">Save search settings</flux:button>
    </form>
</div>
