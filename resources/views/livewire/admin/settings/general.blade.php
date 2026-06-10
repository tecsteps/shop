<form wire:submit="save" class="space-y-6">
    <x-admin.card>
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div>
                <flux:heading>{{ __('Store details') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Basic information about your store.') }}</flux:text>
            </div>

            <div class="space-y-4 lg:col-span-2">
                <flux:field>
                    <flux:label>{{ __('Store name') }}</flux:label>
                    <flux:input wire:model="storeName" data-test="store-name-input" />
                    <flux:error name="storeName" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Store handle') }}</flux:label>
                    <flux:input wire:model="storeHandle" disabled />
                    <flux:description>{{ __('The store handle cannot be changed after creation.') }}</flux:description>
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Contact email') }}</flux:label>
                    <flux:input wire:model="contactEmail" type="email" placeholder="hello@example.com" data-test="contact-email-input" />
                    <flux:error name="contactEmail" />
                </flux:field>

                <flux:field class="max-w-40">
                    <flux:label>{{ __('Order number prefix') }}</flux:label>
                    <flux:input wire:model="orderNumberPrefix" placeholder="#" data-test="order-prefix-input" />
                    <flux:error name="orderNumberPrefix" />
                </flux:field>
            </div>
        </div>

        <flux:separator class="my-6" />

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div>
                <flux:heading>{{ __('Defaults') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Currency, language, and timezone settings.') }}</flux:text>
            </div>

            <div class="space-y-4 lg:col-span-2">
                <flux:field>
                    <flux:label>{{ __('Default currency') }}</flux:label>
                    <flux:select wire:model="defaultCurrency" data-test="currency-select">
                        @foreach (['EUR', 'USD', 'GBP', 'CHF', 'SEK', 'DKK', 'NOK', 'PLN', 'CAD', 'AUD', 'JPY'] as $currency)
                            <flux:select.option value="{{ $currency }}">{{ $currency }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="defaultCurrency" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Default locale') }}</flux:label>
                    <flux:select wire:model="defaultLocale" data-test="locale-select">
                        <flux:select.option value="en">{{ __('English') }}</flux:select.option>
                        <flux:select.option value="de">{{ __('German') }}</flux:select.option>
                        <flux:select.option value="fr">{{ __('French') }}</flux:select.option>
                        <flux:select.option value="es">{{ __('Spanish') }}</flux:select.option>
                        <flux:select.option value="it">{{ __('Italian') }}</flux:select.option>
                    </flux:select>
                    <flux:error name="defaultLocale" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Timezone') }}</flux:label>
                    <flux:select wire:model="timezone" data-test="timezone-select">
                        @foreach ($timezones as $timezoneOption)
                            <flux:select.option value="{{ $timezoneOption }}">{{ $timezoneOption }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="timezone" />
                </flux:field>
            </div>
        </div>
    </x-admin.card>

    <div class="flex justify-end">
        <flux:button type="submit" variant="primary" data-test="save-general-settings-button">
            <span wire:loading.remove wire:target="save">{{ __('Save') }}</span>
            <span wire:loading wire:target="save">{{ __('Saving...') }}</span>
        </flux:button>
    </div>
</form>
