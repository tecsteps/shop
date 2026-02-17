<div>
    <div class="mb-6">
        <flux:heading size="xl">Settings</flux:heading>
    </div>

    <div class="mb-6 flex gap-1 border-b border-zinc-200 dark:border-zinc-700">
        <a href="{{ route('admin.settings.index') }}" wire:navigate class="border-b-2 border-transparent px-4 py-2 text-sm font-medium text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300">General</a>
        <a href="{{ route('admin.settings.shipping') }}" wire:navigate class="border-b-2 border-transparent px-4 py-2 text-sm font-medium text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300">Shipping</a>
        <a href="{{ route('admin.settings.taxes') }}" class="border-b-2 border-zinc-900 px-4 py-2 text-sm font-medium text-zinc-900 dark:border-white dark:text-white">Taxes</a>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="md" class="mb-4">Tax mode</flux:heading>
            <div class="mb-4 space-y-3">
                <flux:radio wire:model.live="mode" value="manual" label="Manual" description="Configure tax rates manually" />
                <flux:radio wire:model.live="mode" value="provider" label="Provider" description="Use external tax provider" />
            </div>
            <flux:switch wire:model="pricesIncludeTax" label="Prices include tax" />
        </div>

        @if ($mode === 'manual')
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="md" class="mb-4">Tax rates</flux:heading>
                @foreach ($rates as $index => $rate)
                    <div class="mb-3 flex items-end gap-4">
                        <div class="flex-1">
                            <flux:field>
                                <flux:label>Name</flux:label>
                                <flux:input wire:model="rates.{{ $index }}.name" placeholder="VAT" />
                            </flux:field>
                        </div>
                        <div class="w-32">
                            <flux:field>
                                <flux:label>Rate (%)</flux:label>
                                <flux:input wire:model="rates.{{ $index }}.rate" type="number" step="0.01" placeholder="19" />
                            </flux:field>
                        </div>
                        <flux:button variant="ghost" icon="trash" wire:click="removeRate({{ $index }})" class="mb-1" />
                    </div>
                @endforeach
                <flux:button variant="ghost" icon="plus" size="sm" wire:click="addRate">Add rate</flux:button>
            </div>
        @endif

        <div class="flex justify-end">
            <flux:button variant="primary" type="submit">Save</flux:button>
        </div>
    </form>
</div>
