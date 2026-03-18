<div>
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Settings') }}</flux:heading>
    </div>

    <div class="flex gap-4 mb-6">
        <flux:button :href="route('admin.settings.index')" :variant="request()->routeIs('admin.settings.index') ? 'primary' : 'ghost'" wire:navigate>
            {{ __('General') }}
        </flux:button>
        <flux:button :href="route('admin.settings.shipping')" :variant="request()->routeIs('admin.settings.shipping') ? 'primary' : 'ghost'" wire:navigate>
            {{ __('Shipping') }}
        </flux:button>
        <flux:button :href="route('admin.settings.taxes')" :variant="request()->routeIs('admin.settings.taxes') ? 'primary' : 'ghost'" wire:navigate>
            {{ __('Taxes') }}
        </flux:button>
    </div>

    <form wire:submit="save">
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6 space-y-4">
            <flux:heading size="md">{{ __('General') }}</flux:heading>

            <flux:field>
                <flux:label>{{ __('Store name') }}</flux:label>
                <flux:input wire:model="storeName" />
                <flux:error name="storeName" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Default currency') }}</flux:label>
                <flux:input wire:model="defaultCurrency" maxlength="3" placeholder="EUR" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Timezone') }}</flux:label>
                <flux:input wire:model="timezone" placeholder="UTC" />
            </flux:field>
        </div>

        <div class="mt-6 flex justify-end">
            <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
        </div>
    </form>
</div>
