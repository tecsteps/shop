<div class="max-w-3xl">
    <flux:heading size="xl">Taxes</flux:heading>

    <div class="mt-6 space-y-6">
        {{-- Mode selection --}}
        <flux:card class="p-6">
            <flux:heading size="md">Tax calculation</flux:heading>

            <div class="mt-4 space-y-3">
                <flux:radio wire:model="mode" value="manual" label="Manual tax rates" description="Define tax rates per zone manually" />
                <flux:radio wire:model="mode" value="provider" label="Tax provider" description="Use an automated tax calculation service" />
            </div>
        </flux:card>

        {{-- Manual rates --}}
        @if ($mode === 'manual')
            <flux:card class="p-6">
                <flux:heading size="md">Manual tax rates</flux:heading>

                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-zinc-200 text-start text-xs uppercase tracking-wider text-zinc-400 dark:border-zinc-700">
                                <th class="py-2 pe-3 text-start font-medium">Zone name</th>
                                <th class="py-2 pe-3 text-start font-medium">Rate (%)</th>
                                <th class="py-2 text-end font-medium"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($manualRates as $index => $rate)
                                <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800">
                                    <td class="py-2 pe-3">
                                        <flux:input wire:model="manualRates.{{ $index }}.zone_name" placeholder="EU" class="w-full" />
                                    </td>
                                    <td class="py-2 pe-3">
                                        <flux:input wire:model="manualRates.{{ $index }}.rate_percentage" type="number" step="0.01" min="0" max="100" class="w-28" />
                                    </td>
                                    <td class="py-2 text-end">
                                        <flux:button variant="ghost" size="sm" icon="trash" wire:click="removeManualRate({{ $index }})" aria-label="Remove rate" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <flux:button variant="ghost" size="sm" icon="plus" wire:click="addManualRate" class="mt-3">
                    Add rate
                </flux:button>
            </flux:card>
        @else
            <flux:card class="p-6">
                <flux:heading size="md">Provider configuration</flux:heading>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>Provider</flux:label>
                        <flux:select wire:model="provider">
                            <option value="stripe_tax">Stripe Tax</option>
                            <option value="none">None</option>
                        </flux:select>
                    </flux:field>
                    <flux:field>
                        <flux:label>API key</flux:label>
                        <flux:input type="password" wire:model="providerApiKey" placeholder="sk_..." />
                    </flux:field>
                </div>
            </flux:card>
        @endif

        <flux:separator />

        {{-- Tax-inclusive toggle --}}
        <flux:card class="p-6">
            <flux:switch wire:model="pricesIncludeTax" label="Prices include tax" />
            <flux:text class="mt-2">
                When enabled, the listed price includes tax. Tax is calculated backwards from the price.
            </flux:text>
        </flux:card>

        <div class="flex justify-end">
            <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled">Save</flux:button>
        </div>
    </div>
</div>
