<div class="space-y-6">
    <x-admin.breadcrumbs :items="[
        ['label' => __('Settings'), 'href' => route('admin.settings.index')],
        ['label' => __('Taxes')],
    ]" />

    <flux:heading size="xl" level="1">{{ __('Settings') }}</flux:heading>

    <x-admin.settings-tabs active="taxes" />

    <form wire:submit="save" class="space-y-6">
        <x-admin.card :heading="__('Tax calculation')" class="space-y-4">
            <flux:radio.group wire:model.live="mode">
                <flux:radio value="manual" :label="__('Manual tax rates')" :description="__('Define tax rates manually')" data-test="tax-mode-manual" />
                <flux:radio value="provider" :label="__('Tax provider')" :description="__('Use an automated tax calculation service')" data-test="tax-mode-provider" />
            </flux:radio.group>
            <flux:error name="mode" />

            @if ($mode === 'manual')
                <div class="grid max-w-md grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>{{ __('Tax rate (%)') }}</flux:label>
                        <flux:input wire:model="manualRate" type="number" step="0.01" min="0" max="100" placeholder="19.00" data-test="manual-rate-input" />
                        <flux:error name="manualRate" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Tax name') }}</flux:label>
                        <flux:input wire:model="taxName" placeholder="VAT" data-test="tax-name-input" />
                        <flux:error name="taxName" />
                    </flux:field>
                </div>
            @else
                <div class="grid max-w-md grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>{{ __('Provider') }}</flux:label>
                        <flux:select wire:model="provider" data-test="tax-provider-select">
                            <flux:select.option value="none">{{ __('None') }}</flux:select.option>
                            <flux:select.option value="stripe_tax">{{ __('Stripe Tax') }}</flux:select.option>
                        </flux:select>
                        <flux:error name="provider" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('API key') }}</flux:label>
                        <flux:input wire:model="providerApiKey" type="password" data-test="tax-api-key-input" />
                        <flux:error name="providerApiKey" />
                    </flux:field>
                </div>
            @endif
        </x-admin.card>

        <x-admin.card :heading="__('Tax behavior')" class="space-y-5">
            <flux:field variant="inline">
                <flux:switch wire:model="pricesIncludeTax" data-test="prices-include-tax-switch" />
                <flux:label>{{ __('Prices include tax') }}</flux:label>
            </flux:field>
            <flux:text class="text-sm">
                {{ __('When enabled, the listed price includes tax. Tax is calculated backwards from the price.') }}
            </flux:text>

            <flux:separator />

            <flux:field variant="inline">
                <flux:switch wire:model="shippingTaxable" data-test="shipping-taxable-switch" />
                <flux:label>{{ __('Charge tax on shipping') }}</flux:label>
            </flux:field>
        </x-admin.card>

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary" data-test="save-tax-settings-button">
                <span wire:loading.remove wire:target="save">{{ __('Save') }}</span>
                <span wire:loading wire:target="save">{{ __('Saving...') }}</span>
            </flux:button>
        </div>
    </form>
</div>
