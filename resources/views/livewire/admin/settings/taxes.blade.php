<div class="space-y-6">
    <flux:heading size="xl">Taxes</flux:heading>

    {{-- Mode selection (spec 03 §11.4) --}}
    <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="md">Tax mode</flux:heading>

        <flux:radio.group wire:model.live="mode" label="" class="mt-4">
            <flux:radio value="manual" label="Manual tax rates" description="Define tax rates per zone manually." />
            <flux:radio value="provider" label="Tax provider" description="Use an automated tax calculation service." />
        </flux:radio.group>
        <flux:error name="mode" />
    </div>

    @if ($mode === 'provider')
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="md">Provider configuration</flux:heading>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <flux:field>
                    <flux:label for="provider">Provider</flux:label>
                    <flux:select id="provider" wire:model="provider">
                        <flux:select.option value="manual">None</flux:select.option>
                        <flux:select.option value="stripe">Stripe Tax</flux:select.option>
                    </flux:select>
                    <flux:error name="provider" />
                </flux:field>

                <flux:field>
                    <flux:label for="fallback">On provider failure</flux:label>
                    <flux:select id="fallback" wire:model="fallback">
                        <flux:select.option value="block">Block checkout</flux:select.option>
                        <flux:select.option value="allow">Allow checkout without tax</flux:select.option>
                    </flux:select>
                    <flux:error name="fallback" />
                </flux:field>
            </div>
        </div>
    @endif

    {{-- Rates (spec 05 §8.2) --}}
    <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="md">Rates</flux:heading>

        <flux:field class="mt-4">
            <flux:label for="defaultRateBps">Default rate (basis points)</flux:label>
            <flux:input id="defaultRateBps" type="number" min="0" max="10000" wire:model.blur="defaultRateBps" placeholder="1900" />
            <flux:description>1900 = 19.00%. Applied when no zone override matches.</flux:description>
            <flux:error name="defaultRateBps" />
        </flux:field>

        @if ($zones->isNotEmpty())
            <flux:separator class="my-4" />
            <flux:text class="text-sm font-medium text-zinc-900 dark:text-zinc-100">Zone overrides</flux:text>
            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Optional per-zone rates. Leave empty to use the default rate.</flux:text>

            <div class="mt-3 grid gap-4 sm:grid-cols-2">
                @foreach ($zones as $zone)
                    <flux:field wire:key="zone-rate-{{ $zone->id }}">
                        <flux:label for="zoneRates-{{ $zone->id }}">{{ $zone->name }}</flux:label>
                        <flux:input id="zoneRates-{{ $zone->id }}" type="number" min="0" max="10000" wire:model.blur="zoneRates.{{ $zone->id }}" placeholder="Default" />
                        <flux:error name="zoneRates.{{ $zone->id }}" />
                    </flux:field>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Tax-inclusive toggle (spec 05 §8.3) --}}
    <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:switch wire:model="pricesIncludeTax" label="Prices include tax" description="When enabled, the listed price includes tax. Tax is calculated backwards from the price." />
    </div>

    <div class="flex justify-end">
        <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled" wire:target="save">
            <span wire:loading.remove wire:target="save">Save</span>
            <span wire:loading wire:target="save">Saving...</span>
        </flux:button>
    </div>
</div>
