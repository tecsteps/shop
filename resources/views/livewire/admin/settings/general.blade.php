<div>
    <flux:heading size="xl" class="mb-6">Settings</flux:heading>

    {{-- Store details --}}
    <div class="mb-8 grid grid-cols-1 gap-8 lg:grid-cols-3">
        <div>
            <flux:heading size="md">Store details</flux:heading>
            <flux:text class="mt-1 text-sm text-gray-500">Basic information about your store.</flux:text>
        </div>
        <div class="space-y-4 lg:col-span-2">
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
                <div class="space-y-4">
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
                </div>
            </div>
        </div>
    </div>

    <flux:separator class="my-8" />

    {{-- Defaults --}}
    <div class="mb-8 grid grid-cols-1 gap-8 lg:grid-cols-3">
        <div>
            <flux:heading size="md">Defaults</flux:heading>
            <flux:text class="mt-1 text-sm text-gray-500">Currency, language, and timezone settings.</flux:text>
        </div>
        <div class="space-y-4 lg:col-span-2">
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
                <div class="space-y-4">
                    <flux:field>
                        <flux:label>Default currency</flux:label>
                        <flux:select wire:model="defaultCurrency">
                            <option value="EUR">EUR - Euro</option>
                            <option value="USD">USD - US Dollar</option>
                            <option value="GBP">GBP - British Pound</option>
                            <option value="CHF">CHF - Swiss Franc</option>
                            <option value="JPY">JPY - Japanese Yen</option>
                        </flux:select>
                    </flux:field>
                    <flux:field>
                        <flux:label>Default locale</flux:label>
                        <flux:select wire:model="defaultLocale">
                            <option value="en">English</option>
                            <option value="de">German</option>
                            <option value="fr">French</option>
                            <option value="es">Spanish</option>
                            <option value="it">Italian</option>
                        </flux:select>
                    </flux:field>
                    <flux:field>
                        <flux:label>Timezone</flux:label>
                        <flux:select wire:model="timezone">
                            @foreach (timezone_identifiers_list() as $tz)
                                <option value="{{ $tz }}">{{ $tz }}</option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                </div>
            </div>
        </div>
    </div>

    <div class="flex justify-end">
        <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="save">Save</span>
            <span wire:loading wire:target="save">Saving...</span>
        </flux:button>
    </div>
</div>
