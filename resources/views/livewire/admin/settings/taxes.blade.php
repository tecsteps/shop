<div>
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Settings') }}</flux:heading>
    </div>

    <div class="flex gap-4 mb-6">
        <flux:button :href="route('admin.settings.index')" variant="ghost" wire:navigate>{{ __('General') }}</flux:button>
        <flux:button :href="route('admin.settings.shipping')" variant="ghost" wire:navigate>{{ __('Shipping') }}</flux:button>
        <flux:button :href="route('admin.settings.taxes')" variant="primary" wire:navigate>{{ __('Taxes') }}</flux:button>
    </div>

    <form wire:submit="save">
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6 space-y-4">
            <flux:heading size="md">{{ __('Tax configuration') }}</flux:heading>

            <flux:field>
                <flux:label>{{ __('Tax mode') }}</flux:label>
                <flux:select wire:model="mode">
                    <flux:select.option value="manual">{{ __('Manual') }}</flux:select.option>
                    <flux:select.option value="provider">{{ __('Provider') }}</flux:select.option>
                </flux:select>
            </flux:field>

            <flux:checkbox wire:model="pricesIncludeTax" label="{{ __('Prices include tax') }}" />

            <flux:field>
                <flux:label>{{ __('Default tax rate (%)') }}</flux:label>
                <flux:input wire:model="defaultRate" type="number" min="0" max="100" step="0.01" />
                <flux:error name="defaultRate" />
            </flux:field>
        </div>

        <div class="mt-6 flex justify-end">
            <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
        </div>
    </form>
</div>
