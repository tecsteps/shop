<div>
    <x-admin.breadcrumbs :items="[['label' => __('Settings'), 'href' => route('admin.settings.index')], ['label' => __('Taxes')]]" />

    <flux:heading size="xl" level="1" class="mb-6">{{ __('Settings') }}</flux:heading>

    <x-admin.settings-tabs active="taxes" />

    <form wire:submit="save" class="max-w-2xl space-y-6">
        <x-admin.card title="{{ __('Mode') }}">
            <flux:radio.group wire:model.live="mode">
                <flux:radio value="manual" :label="__('Manual tax rates')" description="{{ __('Define tax rates per zone manually') }}" />
                <flux:radio value="provider" :label="__('Tax provider')" description="{{ __('Use an automated tax calculation service') }}" />
            </flux:radio.group>
        </x-admin.card>

        @if ($mode === 'manual')
            <x-admin.card title="{{ __('Manual rates') }}">
                <div class="space-y-3">
                    @foreach ($manualRates as $index => $rate)
                        <div class="flex items-end gap-3" wire:key="rate-row-{{ $index }}">
                            <flux:field class="flex-1">
                                <flux:label>{{ __('Zone name') }}</flux:label>
                                <flux:input wire:model="manualRates.{{ $index }}.zone_name" placeholder="EU" />
                            </flux:field>
                            <flux:field class="w-32">
                                <flux:label>{{ __('Rate (%)') }}</flux:label>
                                <flux:input type="number" step="0.01" wire:model="manualRates.{{ $index }}.rate_percentage" data-test="tax-rate-{{ $index }}" />
                            </flux:field>
                            <flux:button type="button" variant="ghost" icon="trash" wire:click="removeManualRate({{ $index }})" :aria-label="__('Remove rate')" />
                        </div>
                    @endforeach
                </div>
                <div class="mt-3">
                    <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addManualRate">{{ __('Add rate') }}</flux:button>
                </div>
            </x-admin.card>
        @else
            <x-admin.card title="{{ __('Provider') }}">
                <flux:field>
                    <flux:label>{{ __('Provider') }}</flux:label>
                    <flux:select wire:model="provider">
                        <flux:select.option value="">{{ __('None') }}</flux:select.option>
                        <flux:select.option value="stripe_tax">{{ __('Stripe Tax') }}</flux:select.option>
                    </flux:select>
                </flux:field>
                <flux:field class="mt-4">
                    <flux:label>{{ __('API key') }}</flux:label>
                    <flux:input type="password" wire:model="providerApiKey" viewable />
                </flux:field>
            </x-admin.card>
        @endif

        <x-admin.card>
            <flux:switch wire:model="pricesIncludeTax" :label="__('Prices include tax')" data-test="prices-include-tax" />
            <flux:text class="mt-2 text-sm">{{ __('When enabled, the listed price includes tax. Tax is calculated backwards from the price.') }}</flux:text>
        </x-admin.card>

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary" data-test="save-taxes">{{ __('Save') }}</flux:button>
        </div>
    </form>
</div>
