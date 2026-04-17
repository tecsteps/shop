<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Tax Settings</flux:heading>
        <a href="{{ route('admin.settings.index') }}">
            <flux:button variant="ghost">Back to Settings</flux:button>
        </a>
    </div>

    @if (session('message'))
        <div class="rounded-md bg-green-50 p-3 text-sm text-green-700 dark:bg-green-900/30 dark:text-green-400">
            {{ session('message') }}
        </div>
    @endif

    <form wire:submit="save" class="space-y-4">
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:select wire:model="mode" label="Tax Mode">
                <option value="manual">Manual</option>
                <option value="provider">Provider</option>
            </flux:select>

            <div class="mt-4">
                <flux:checkbox wire:model="pricesIncludeTax" label="Prices include tax" />
            </div>

            <div class="mt-4">
                <flux:input wire:model="defaultRate" label="Default Tax Rate (basis points, e.g. 1000 = 10%)" type="number" />
            </div>
        </div>

        <flux:button type="submit" variant="primary">Save Tax Settings</flux:button>
    </form>
</div>
