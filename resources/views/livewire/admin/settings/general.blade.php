<div>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div>
            <flux:heading size="md">Store details</flux:heading>
            <flux:text variant="subtle" class="mt-1">Basic information about your store.</flux:text>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-6 lg:col-span-2 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:field>
                <flux:label>Store name</flux:label>
                <flux:input wire:model="storeName" />
                <flux:error name="storeName" />
            </flux:field>
            <flux:field class="mt-4">
                <flux:label>Store handle</flux:label>
                <flux:input wire:model="storeHandle" disabled />
                <flux:description>The store handle cannot be changed after creation.</flux:description>
            </flux:field>
            <flux:field class="mt-4">
                <flux:label>Contact email</flux:label>
                <flux:input wire:model="contactEmail" type="email" />
                <flux:error name="contactEmail" />
            </flux:field>
        </div>
    </div>

    <flux:separator class="my-6" />

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div>
            <flux:heading size="md">Defaults</flux:heading>
            <flux:text variant="subtle" class="mt-1">Currency, language, and timezone settings.</flux:text>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-6 lg:col-span-2 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:field>
                <flux:label>Default currency</flux:label>
                <flux:select wire:model="defaultCurrency">
                    @foreach (['EUR', 'USD', 'GBP', 'CHF', 'JPY', 'CAD', 'AUD'] as $currency)
                        <flux:select.option value="{{ $currency }}">{{ $currency }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="defaultCurrency" />
            </flux:field>
            <flux:field class="mt-4">
                <flux:label>Default locale</flux:label>
                <flux:select wire:model="defaultLocale">
                    <flux:select.option value="en">English</flux:select.option>
                    <flux:select.option value="de">German</flux:select.option>
                    <flux:select.option value="fr">French</flux:select.option>
                    <flux:select.option value="es">Spanish</flux:select.option>
                    <flux:select.option value="it">Italian</flux:select.option>
                </flux:select>
                <flux:error name="defaultLocale" />
            </flux:field>
            <flux:field class="mt-4">
                <flux:label>Timezone</flux:label>
                <flux:select wire:model="timezone">
                    @foreach ($timezones as $timezone)
                        <flux:select.option value="{{ $timezone }}">{{ $timezone }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="timezone" />
            </flux:field>
        </div>
    </div>

    <div class="mt-6 flex justify-end">
        <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="save">Save</span>
            <span wire:loading wire:target="save">Saving...</span>
        </flux:button>
    </div>
</div>
