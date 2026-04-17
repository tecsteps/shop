<div>
    <flux:heading size="xl" class="mb-6">Tax settings</flux:heading>

    <div class="max-w-2xl space-y-6">
        {{-- Mode Selection --}}
        <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
            <flux:radio.group wire:model.live="mode" label="Tax calculation mode">
                <flux:radio value="manual" label="Manual tax rates" description="Define tax rates per zone manually" />
                <flux:radio value="provider" label="Tax provider" description="Use an automated tax calculation service" />
            </flux:radio.group>
        </div>

        {{-- Manual Rates --}}
        @if ($mode === 'manual')
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
                <flux:heading size="md" class="mb-4">Manual rates</flux:heading>
                @foreach ($manualRates as $index => $rate)
                    <div class="mb-3 flex items-end gap-3" wire:key="rate-{{ $index }}">
                        <div class="flex-1">
                            <flux:field>
                                <flux:label>Zone name</flux:label>
                                <flux:input wire:model="manualRates.{{ $index }}.zone_name" placeholder="EU" />
                            </flux:field>
                        </div>
                        <div class="w-32">
                            <flux:field>
                                <flux:label>Rate (%)</flux:label>
                                <flux:input wire:model="manualRates.{{ $index }}.rate_percentage" type="number" step="0.01" />
                            </flux:field>
                        </div>
                        <flux:button variant="ghost" icon="trash" wire:click="removeManualRate({{ $index }})" class="text-red-500" />
                    </div>
                @endforeach
                <flux:button variant="ghost" wire:click="addManualRate" size="sm">
                    <flux:icon name="plus" variant="mini" class="mr-1 h-4 w-4" />
                    Add rate
                </flux:button>
            </div>
        @endif

        {{-- Provider Config --}}
        @if ($mode === 'provider')
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
                <flux:heading size="md" class="mb-4">Provider configuration</flux:heading>
                <div class="space-y-4">
                    <flux:field>
                        <flux:label>Provider</flux:label>
                        <flux:select wire:model="provider">
                            <option value="">None</option>
                            <option value="stripe_tax">Stripe Tax</option>
                        </flux:select>
                    </flux:field>
                    <flux:field>
                        <flux:label>API key</flux:label>
                        <flux:input wire:model="providerApiKey" type="password" />
                    </flux:field>
                </div>
            </div>
        @endif

        <flux:separator />

        {{-- Tax-inclusive Toggle --}}
        <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
            <flux:switch wire:model="pricesIncludeTax" label="Prices include tax" description="When enabled, the listed price includes tax. Tax is calculated backwards from the price." />
        </div>

        <div class="flex justify-end">
            <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">Save</span>
                <span wire:loading wire:target="save">Saving...</span>
            </flux:button>
        </div>
    </div>
</div>
