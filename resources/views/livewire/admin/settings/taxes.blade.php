<div class="mx-auto max-w-2xl">
    <flux:heading size="xl" class="mb-6">Taxes</flux:heading>
    @if (session('success'))
        <flux:callout variant="success" heading="{{ session('success') }}" class="mb-4"></flux:callout>
    @endif
    <form wire:submit="save" class="flex flex-col gap-4 rounded-xl bg-white p-6 ring-1 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700">
        <flux:select label="Mode" wire:model="mode">
            <flux:select.option value="manual">Manual</flux:select.option>
            <flux:select.option value="provider">Provider</flux:select.option>
        </flux:select>
        <flux:input type="number" step="0.001" label="Default rate (e.g. 0.19 for 19%)" wire:model="defaultRate" />
        <flux:checkbox label="Prices include tax" wire:model="pricesIncludeTax" />
        <div class="flex justify-end">
            <flux:button type="submit" variant="primary">Save</flux:button>
        </div>
    </form>
</div>
