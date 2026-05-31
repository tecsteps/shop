<div>
    <x-admin.breadcrumbs :items="[['label' => __('Settings')]]" />

    <flux:heading size="xl" level="1" class="mb-6">{{ __('Settings') }}</flux:heading>

    <x-admin.settings-tabs :active="$tab" />

    @if ($tab === 'general')
        <form wire:submit="save" class="space-y-8">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div>
                    <flux:heading size="md">{{ __('Store details') }}</flux:heading>
                    <flux:text class="mt-1 text-sm">{{ __('Basic information about your store.') }}</flux:text>
                </div>
                <div class="space-y-4 lg:col-span-2">
                    <flux:field>
                        <flux:label>{{ __('Store name') }}</flux:label>
                        <flux:input wire:model="storeName" data-test="store-name" />
                        <flux:error name="storeName" />
                    </flux:field>
                    <flux:field>
                        <flux:label>{{ __('Store handle') }}</flux:label>
                        <flux:input wire:model="storeHandle" disabled />
                        <flux:description>{{ __('The store handle cannot be changed after creation.') }}</flux:description>
                    </flux:field>
                </div>
            </div>

            <flux:separator />

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div>
                    <flux:heading size="md">{{ __('Defaults') }}</flux:heading>
                    <flux:text class="mt-1 text-sm">{{ __('Currency, language, and timezone settings.') }}</flux:text>
                </div>
                <div class="space-y-4 lg:col-span-2">
                    <flux:field>
                        <flux:label>{{ __('Default currency') }}</flux:label>
                        <flux:select wire:model="defaultCurrency">
                            @foreach (['EUR', 'USD', 'GBP', 'CHF', 'CAD', 'AUD'] as $code)
                                <flux:select.option :value="$code">{{ $code }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                    <flux:field>
                        <flux:label>{{ __('Default locale') }}</flux:label>
                        <flux:select wire:model="defaultLocale">
                            <flux:select.option value="en">English</flux:select.option>
                            <flux:select.option value="de">Deutsch</flux:select.option>
                            <flux:select.option value="fr">Français</flux:select.option>
                            <flux:select.option value="es">Español</flux:select.option>
                        </flux:select>
                    </flux:field>
                    <flux:field>
                        <flux:label>{{ __('Timezone') }}</flux:label>
                        <flux:select wire:model="timezone">
                            @foreach ($this->timezones as $tz)
                                <flux:select.option :value="$tz">{{ $tz }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="timezone" />
                    </flux:field>
                </div>
            </div>

            <div class="flex justify-end">
                <flux:button type="submit" variant="primary" data-test="save-settings">{{ __('Save') }}</flux:button>
            </div>
        </form>
    @elseif ($tab === 'domains')
        <livewire:admin.settings.domains />
    @elseif ($tab === 'checkout')
        <livewire:admin.settings.checkout />
    @elseif ($tab === 'notifications')
        <livewire:admin.settings.notifications />
    @endif
</div>
