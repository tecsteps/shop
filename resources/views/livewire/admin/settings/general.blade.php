<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Settings</flux:heading>
    </div>

    <div class="flex flex-wrap gap-2 border-b border-neutral-200 pb-2 dark:border-neutral-800">
        <a href="{{ url('/admin/settings') }}" class="rounded-md px-3 py-1 text-sm font-medium bg-neutral-100 dark:bg-neutral-800">General</a>
        <a href="{{ url('/admin/settings/shipping') }}" class="rounded-md px-3 py-1 text-sm text-neutral-600 hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800">Shipping</a>
        <a href="{{ url('/admin/settings/taxes') }}" class="rounded-md px-3 py-1 text-sm text-neutral-600 hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800">Taxes</a>
        <a href="{{ url('/admin/settings/staff') }}" class="rounded-md px-3 py-1 text-sm text-neutral-600 hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800">Staff</a>
    </div>

    <form wire:submit="save" class="space-y-4 rounded-lg border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
        <flux:heading size="md">Store details</flux:heading>
        <flux:field>
            <flux:label>Store name</flux:label>
            <flux:input wire:model="storeName" />
            <flux:error name="storeName" />
        </flux:field>
        <flux:field>
            <flux:label>Store handle</flux:label>
            <flux:input wire:model="storeHandle" disabled />
            <flux:description>The store handle cannot be changed after creation.</flux:description>
        </flux:field>

        <flux:separator />

        <flux:heading size="md">Defaults</flux:heading>
        <flux:field>
            <flux:label>Default currency</flux:label>
            <flux:select wire:model="defaultCurrency">
                <flux:select.option value="USD">USD</flux:select.option>
                <flux:select.option value="EUR">EUR</flux:select.option>
                <flux:select.option value="GBP">GBP</flux:select.option>
            </flux:select>
        </flux:field>
        <flux:field>
            <flux:label>Default locale</flux:label>
            <flux:select wire:model="defaultLocale">
                <flux:select.option value="en">English</flux:select.option>
                <flux:select.option value="de">German</flux:select.option>
                <flux:select.option value="fr">French</flux:select.option>
            </flux:select>
        </flux:field>
        <flux:field>
            <flux:label>Timezone</flux:label>
            <flux:input wire:model="timezone" />
        </flux:field>

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary">Save</flux:button>
        </div>
    </form>
</div>
