<div>
    <form wire:submit="save" class="space-y-8">
        {{-- Store details --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div>
                <flux:heading size="lg">Store details</flux:heading>
                <flux:text class="mt-1">Basic information about your store.</flux:text>
            </div>
            <div class="lg:col-span-2 space-y-4">
                <flux:input
                    wire:model="storeName"
                    label="Store name"
                    placeholder="My Store"
                    required
                />
                @error('storeName')
                    <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror

                <flux:input
                    wire:model="storeHandle"
                    label="Store handle"
                    disabled
                    description="The store handle cannot be changed after creation."
                />
            </div>
        </div>

        <flux:separator />

        {{-- Defaults --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div>
                <flux:heading size="lg">Defaults</flux:heading>
                <flux:text class="mt-1">Currency, language, and timezone settings.</flux:text>
            </div>
            <div class="lg:col-span-2 space-y-4">
                <flux:select wire:model="defaultCurrency" label="Default currency">
                    <option value="EUR">EUR - Euro</option>
                    <option value="USD">USD - US Dollar</option>
                    <option value="GBP">GBP - British Pound</option>
                    <option value="CHF">CHF - Swiss Franc</option>
                    <option value="JPY">JPY - Japanese Yen</option>
                    <option value="CAD">CAD - Canadian Dollar</option>
                    <option value="AUD">AUD - Australian Dollar</option>
                </flux:select>

                <flux:select wire:model="defaultLocale" label="Default locale">
                    <option value="en">English</option>
                    <option value="de">German</option>
                    <option value="fr">French</option>
                    <option value="es">Spanish</option>
                    <option value="it">Italian</option>
                    <option value="nl">Dutch</option>
                    <option value="pt">Portuguese</option>
                </flux:select>

                <flux:select wire:model="timezone" label="Timezone">
                    @foreach (timezone_identifiers_list() as $tz)
                        <option value="{{ $tz }}">{{ $tz }}</option>
                    @endforeach
                </flux:select>
            </div>
        </div>

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">Save</span>
                <span wire:loading wire:target="save">Saving...</span>
            </flux:button>
        </div>
    </form>
</div>
