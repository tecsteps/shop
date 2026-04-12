<div class="space-y-6 p-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Taxes</flux:heading>
        <flux:button :href="route('admin.settings.index')" variant="ghost" wire:navigate>Back</flux:button>
    </div>

    @if (session('status'))
        <div class="rounded border border-green-200 bg-green-50 p-3 text-sm text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-200">
            {{ session('status') }}
        </div>
    @endif

    <form wire:submit="save" class="max-w-2xl space-y-4 rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:field>
            <flux:label>Mode</flux:label>
            <flux:select wire:model="mode">
                <flux:select.option value="manual">Manual</flux:select.option>
                <flux:select.option value="provider">Provider</flux:select.option>
            </flux:select>
            <flux:error name="mode" />
        </flux:field>

        <flux:field>
            <flux:label>Tax name</flux:label>
            <flux:input wire:model="taxName" placeholder="VAT" />
        </flux:field>

        <flux:field>
            <flux:label>Rate (basis points)</flux:label>
            <flux:input type="number" wire:model="rateBasisPoints" />
            <flux:description>1900 = 19%. Basis points are 1/100 of a percent.</flux:description>
            <flux:error name="rateBasisPoints" />
        </flux:field>

        <flux:field variant="inline">
            <flux:checkbox wire:model="pricesIncludeTax" />
            <flux:label>Prices include tax</flux:label>
        </flux:field>

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary">Save taxes</flux:button>
        </div>
    </form>
</div>
