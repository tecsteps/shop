<div>
    <flux:heading size="xl" class="mb-6">Taxes</flux:heading>

    <form wire:submit="save" class="space-y-8">
        {{-- Mode selection --}}
        <div>
            <flux:heading size="lg" class="mb-4">Tax mode</flux:heading>
            <div class="space-y-3">
                <label class="flex items-start gap-3 p-4 border rounded-lg cursor-pointer {{ $mode === 'manual' ? 'border-zinc-900 dark:border-white bg-zinc-50 dark:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700' }}">
                    <flux:radio wire:model.live="mode" value="manual" />
                    <div>
                        <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">Manual tax rates</span>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Define tax rates per zone manually.</p>
                    </div>
                </label>
                <label class="flex items-start gap-3 p-4 border rounded-lg cursor-pointer {{ $mode === 'provider' ? 'border-zinc-900 dark:border-white bg-zinc-50 dark:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700' }}">
                    <flux:radio wire:model.live="mode" value="provider" />
                    <div>
                        <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">Tax provider</span>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Use an automated tax calculation service.</p>
                    </div>
                </label>
            </div>
        </div>

        {{-- Manual rates --}}
        @if ($mode === 'manual')
            <div>
                <flux:heading size="lg" class="mb-4">Manual rates</flux:heading>
                <div class="space-y-3">
                    @foreach ($manualRates as $index => $rate)
                        <div wire:key="rate-{{ $index }}" class="flex items-end gap-3">
                            <div class="flex-1">
                                <flux:input
                                    wire:model="manualRates.{{ $index }}.zone_name"
                                    label="{{ $index === 0 ? 'Zone name' : '' }}"
                                    placeholder="EU, US-CA..."
                                />
                            </div>
                            <div class="w-32">
                                <flux:input
                                    wire:model="manualRates.{{ $index }}.rate_percentage"
                                    label="{{ $index === 0 ? 'Rate (%)' : '' }}"
                                    type="number"
                                    step="0.01"
                                    placeholder="19.00"
                                />
                            </div>
                            <flux:button variant="ghost" wire:click="removeManualRate({{ $index }})">
                                <flux:icon name="trash" class="size-4 text-red-500" />
                            </flux:button>
                        </div>
                    @endforeach
                </div>
                <div class="mt-3">
                    <flux:button size="sm" variant="ghost" wire:click="addManualRate">
                        <flux:icon name="plus" class="size-4 mr-1" /> Add rate
                    </flux:button>
                </div>
            </div>
        @endif

        {{-- Provider config --}}
        @if ($mode === 'provider')
            <div>
                <flux:heading size="lg" class="mb-4">Provider configuration</flux:heading>
                <div class="space-y-4 max-w-md">
                    <flux:select wire:model="provider" label="Provider">
                        <option value="">Select a provider</option>
                        <option value="stripe_tax">Stripe Tax</option>
                    </flux:select>

                    <flux:input
                        wire:model="providerApiKey"
                        label="API key"
                        type="password"
                        placeholder="Enter your API key"
                    />
                </div>
            </div>
        @endif

        <flux:separator />

        {{-- Tax-inclusive toggle --}}
        <div class="flex items-start gap-4">
            <flux:switch wire:model="pricesIncludeTax" />
            <div>
                <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">Prices include tax</span>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                    When enabled, the listed price includes tax. Tax is calculated backwards from the price.
                </p>
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
