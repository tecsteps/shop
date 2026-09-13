<div>
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" wire:navigate>Home</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('admin.settings.index') }}" wire:navigate>Settings</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Taxes</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:heading size="xl" class="mb-6">Taxes</flux:heading>

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-4">
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:radio.group wire:model.live="mode" label="Mode selection">
                <flux:radio value="manual" label="Manual tax rates" description="Define tax rates per zone manually" />
                <flux:radio value="provider" label="Tax provider" description="Use an automated tax calculation service" />
            </flux:radio.group>
        </div>

        @if ($this->mode === 'manual')
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="md">Manual rates</flux:heading>
                <table class="mt-4 w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                            <th class="pb-2 font-medium">Zone name</th>
                            <th class="pb-2 font-medium">Rate (%)</th>
                            <th class="pb-2 font-medium"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->manualRates as $index => $rate)
                            <tr wire:key="rate-{{ $index }}">
                                <td class="py-2 pr-2"><flux:input wire:model="manualRates.{{ $index }}.zone_name" placeholder="EU" aria-label="Zone name" /></td>
                                <td class="py-2 pr-2"><flux:input wire:model="manualRates.{{ $index }}.rate_percentage" type="number" step="0.01" min="0" max="100" aria-label="Rate percent" class="w-32" /></td>
                                <td class="py-2 text-right">
                                    <flux:button variant="ghost" size="sm" wire:click="removeManualRate({{ $index }})" aria-label="Remove rate">
                                        <flux:icon.trash class="size-4" variant="outline" />
                                    </flux:button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <flux:button variant="ghost" size="sm" wire:click="addManualRate" class="mt-3">
                    <flux:icon.plus class="size-4" variant="outline" /> Add rate
                </flux:button>
            </div>
        @else
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="md">Provider config</flux:heading>
                <flux:select wire:model="provider" label="Provider" class="mt-4">
                    <flux:select.option value="none">None</flux:select.option>
                    <flux:select.option value="stripe_tax">Stripe Tax</flux:select.option>
                </flux:select>
                <flux:input wire:model="providerApiKey" label="API key" type="password" class="mt-3" />
            </div>
        @endif

        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:separator class="mb-4" />
            <flux:switch wire:model.live="pricesIncludeTax" label="Prices include tax" description="When enabled, the listed price includes tax. Tax is calculated backwards from the price." />
        </div>

        <div class="flex justify-end">
            <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">Save</span>
                <span wire:loading wire:target="save">Saving...</span>
            </flux:button>
        </div>
    </div>
</div>
