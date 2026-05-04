<section class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">Taxes</flux:heading>
            <flux:text class="mt-1">Manual tax rates and provider mode.</flux:text>
        </div>

        <div class="flex flex-wrap gap-2">
            <flux:button :href="route('admin.settings.index')" wire:navigate variant="filled">General</flux:button>
            <flux:button :href="route('admin.settings.shipping')" wire:navigate variant="filled">Shipping</flux:button>
            <flux:button :href="route('admin.settings.taxes')" wire:navigate variant="primary">Taxes</flux:button>
        </div>
    </div>

    @if (session('status'))
        <flux:callout color="green" icon="check-circle">{{ session('status') }}</flux:callout>
    @endif

    <form wire:submit="save" class="space-y-6">
        <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:radio.group wire:model.live="mode" label="Mode" class="grid gap-3 sm:grid-cols-2">
                <flux:radio value="manual" label="Manual tax rates" description="Define tax rates by country." />
                <flux:radio value="provider" label="Tax provider" description="Use an automated tax provider." />
            </flux:radio.group>
            <flux:error name="mode" />
        </div>

        @if ($mode === 'manual')
            <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between gap-4 border-b border-zinc-200 p-5 dark:border-zinc-700">
                    <flux:heading size="lg">Manual rates</flux:heading>
                    <flux:button type="button" wire:click="addManualRate" variant="filled" icon="plus">Add rate</flux:button>
                </div>

                <div class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @foreach ($manualRates as $index => $rate)
                        <div wire:key="manual-tax-rate-{{ $index }}" class="grid gap-3 p-5 md:grid-cols-[120px_1fr_160px_auto] md:items-end">
                            <flux:input wire:model="manualRates.{{ $index }}.country" label="Country" maxlength="2" />
                            <flux:input wire:model="manualRates.{{ $index }}.name" label="Name" />
                            <flux:input wire:model="manualRates.{{ $index }}.rate_percentage" label="Rate %" type="number" step="0.01" min="0" max="100" />
                            <flux:button type="button" wire:click="removeManualRate({{ $index }})" variant="danger" icon="trash" aria-label="Remove tax rate" />
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">Provider</flux:heading>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <flux:select wire:model="provider" label="Provider">
                        <flux:select.option value="none">None</flux:select.option>
                        <flux:select.option value="stripe_tax">Stripe Tax</flux:select.option>
                    </flux:select>
                    <flux:input wire:model="providerApiKey" type="password" label="API key" />
                </div>
            </div>
        @endif

        <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:switch wire:model="pricesIncludeTax" label="Prices include tax" align="left" />
        </div>

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                <span wire:loading.remove>Save taxes</span>
                <span wire:loading>Saving...</span>
            </flux:button>
        </div>
    </form>
</section>
