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

    {{-- Store details section --}}
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3 mb-8">
        <div>
            <flux:heading size="md">Store details</flux:heading>
            <flux:text class="mt-1 text-zinc-500">Basic information about your store.</flux:text>
        </div>
        <div class="lg:col-span-2 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900 space-y-4">
            <flux:field>
                <flux:label>Store name</flux:label>
                <flux:input wire:model="storeName" />
                <flux:error name="storeName" />
            </flux:field>

            <flux:field>
                <flux:label>Store handle</flux:label>
                <flux:input wire:model="storeHandle" disabled />
                <flux:description>The store handle cannot be changed after creation.</flux:description>
            </flux:field>
        </div>
    </div>

    <flux:separator class="my-8" />

    {{-- Defaults section --}}
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3 mb-8">
        <div>
            <flux:heading size="md">Defaults</flux:heading>
            <flux:text class="mt-1 text-zinc-500">Currency, language, and timezone settings.</flux:text>
        </div>
        <div class="lg:col-span-2 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900 space-y-4">
            <flux:field>
                <flux:label>Default currency</flux:label>
                <flux:select wire:model="defaultCurrency">
                    <flux:select.option value="EUR">EUR</flux:select.option>
                    <flux:select.option value="USD">USD</flux:select.option>
                    <flux:select.option value="GBP">GBP</flux:select.option>
                    <flux:select.option value="CHF">CHF</flux:select.option>
                    <flux:select.option value="CAD">CAD</flux:select.option>
                    <flux:select.option value="AUD">AUD</flux:select.option>
                </flux:select>
                <flux:error name="defaultCurrency" />
            </flux:field>

            <flux:field>
                <flux:label>Default locale</flux:label>
                <flux:select wire:model="defaultLocale">
                    <flux:select.option value="en">English</flux:select.option>
                    <flux:select.option value="de">German</flux:select.option>
                    <flux:select.option value="fr">French</flux:select.option>
                    <flux:select.option value="es">Spanish</flux:select.option>
                    <flux:select.option value="it">Italian</flux:select.option>
                    <flux:select.option value="nl">Dutch</flux:select.option>
                </flux:select>
                <flux:error name="defaultLocale" />
            </flux:field>

            <flux:field>
                <flux:label>Timezone</flux:label>
                <flux:select wire:model="timezone">
                    @foreach ($timezones as $tz)
                        <flux:select.option value="{{ $tz }}">{{ $tz }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="timezone" />
            </flux:field>
        </div>
    </div>

    <div class="flex justify-end">
        <flux:button wire:click="save" variant="primary" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="save">Save settings</span>
            <span wire:loading wire:target="save">Saving...</span>
        </flux:button>
    </div>
</div>
