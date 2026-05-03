<form wire:submit="save" class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <flux:heading size="xl">Taxes</flux:heading>
            <flux:text>Manual tax rate and tax-inclusive price settings.</flux:text>
        </div>

        <div class="flex gap-2">
            <flux:button :href="route('admin.settings.index')" wire:navigate>Back to settings</flux:button>
            <flux:button type="submit" variant="primary">Save taxes</flux:button>
        </div>
    </div>

    <section class="max-w-3xl rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="grid gap-4">
            <flux:select wire:model="mode" label="Mode">
                @foreach ($modes as $modeOption)
                    <option value="{{ $modeOption->value }}">{{ ucfirst($modeOption->value) }}</option>
                @endforeach
            </flux:select>
            <flux:switch wire:model="pricesIncludeTax" label="Prices include tax" />
            <flux:input wire:model="manualRate" type="number" min="0" max="10000" label="Manual rate basis points" />
        </div>
    </section>
</form>
