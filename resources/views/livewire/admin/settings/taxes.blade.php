<div>
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" wire:navigate>Home</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Settings</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:heading size="xl" class="mb-6">Settings</flux:heading>

    {{-- Settings tabs --}}
    <div class="flex gap-4 border-b border-zinc-200 dark:border-zinc-700 mb-6">
        <a href="{{ route('admin.settings.index') }}" wire:navigate
            class="pb-2 text-sm font-medium border-b-2 {{ request()->routeIs('admin.settings.index') ? 'border-zinc-900 text-zinc-900 dark:border-white dark:text-white' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}">
            General
        </a>
        <a href="{{ route('admin.settings.shipping') }}" wire:navigate
            class="pb-2 text-sm font-medium border-b-2 {{ request()->routeIs('admin.settings.shipping') ? 'border-zinc-900 text-zinc-900 dark:border-white dark:text-white' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}">
            Shipping
        </a>
        <a href="{{ route('admin.settings.taxes') }}" wire:navigate
            class="pb-2 text-sm font-medium border-b-2 {{ request()->routeIs('admin.settings.taxes') ? 'border-zinc-900 text-zinc-900 dark:border-white dark:text-white' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}">
            Taxes
        </a>
    </div>

    {{-- Tax mode --}}
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3 mb-8">
        <div>
            <flux:heading size="md">Tax mode</flux:heading>
            <flux:text class="mt-1 text-zinc-500">Choose how taxes are calculated.</flux:text>
        </div>
        <div class="lg:col-span-2 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900 space-y-4">
            <flux:field>
                <flux:label>Mode</flux:label>
                <flux:select wire:model.live="mode">
                    @foreach ($modes as $m)
                        <flux:select.option value="{{ $m->value }}">{{ ucfirst($m->value) }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="mode" />
            </flux:field>

            <flux:field>
                <flux:label>Tax name</flux:label>
                <flux:input wire:model="taxName" placeholder="e.g. VAT, GST, Sales Tax" />
                <flux:error name="taxName" />
            </flux:field>
        </div>
    </div>

    <flux:separator class="my-8" />

    {{-- Tax rate --}}
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3 mb-8">
        <div>
            <flux:heading size="md">Tax rate</flux:heading>
            <flux:text class="mt-1 text-zinc-500">Set the default tax rate in basis points.</flux:text>
        </div>
        <div class="lg:col-span-2 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900 space-y-4">
            <flux:field>
                <flux:label>Tax rate (basis points)</flux:label>
                <flux:input wire:model="taxRateBasisPoints" type="number" min="0" max="10000" />
                <flux:description>Enter in basis points: 1900 = 19.00%, 2100 = 21.00%, 700 = 7.00%.</flux:description>
                <flux:error name="taxRateBasisPoints" />
            </flux:field>

            <div class="text-sm text-zinc-500 dark:text-zinc-400">
                Effective rate: <strong>{{ number_format($taxRateBasisPoints / 100, 2) }}%</strong>
            </div>
        </div>
    </div>

    <flux:separator class="my-8" />

    {{-- Tax behavior --}}
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3 mb-8">
        <div>
            <flux:heading size="md">Tax behavior</flux:heading>
            <flux:text class="mt-1 text-zinc-500">Configure how tax is applied.</flux:text>
        </div>
        <div class="lg:col-span-2 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900 space-y-4">
            <flux:switch wire:model="pricesIncludeTax" label="Prices include tax" description="When enabled, the listed price already includes tax. Tax is calculated backwards from the price." />
            <flux:switch wire:model="shippingTaxable" label="Charge tax on shipping" description="Apply the tax rate to shipping costs as well." />
        </div>
    </div>

    <div class="flex justify-end">
        <flux:button wire:click="save" variant="primary" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="save">Save tax settings</span>
            <span wire:loading wire:target="save">Saving...</span>
        </flux:button>
    </div>
</div>
