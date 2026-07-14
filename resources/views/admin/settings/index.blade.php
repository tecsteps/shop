<div class="space-y-6">
    <x-admin.page-header title="Store Settings" description="Manage your store identity, defaults, and domains." />

    <nav class="flex flex-wrap gap-1 border-b border-zinc-200 pb-px dark:border-zinc-800" aria-label="Settings">
        <button type="button" wire:click="selectTab('general')" @class(['border-b-2 px-4 py-2 text-sm font-medium', 'border-blue-600 text-blue-700' => $activeTab === 'general', 'border-transparent text-zinc-500' => $activeTab !== 'general'])>General</button>
        <button type="button" wire:click="selectTab('domains')" @class(['border-b-2 px-4 py-2 text-sm font-medium', 'border-blue-600 text-blue-700' => $activeTab === 'domains', 'border-transparent text-zinc-500' => $activeTab !== 'domains'])>Domains</button>
        <a href="{{ url('/admin/settings/shipping') }}" wire:navigate class="border-b-2 border-transparent px-4 py-2 text-sm font-medium text-zinc-500 hover:text-zinc-900">Shipping</a>
        <a href="{{ url('/admin/settings/taxes') }}" wire:navigate class="border-b-2 border-transparent px-4 py-2 text-sm font-medium text-zinc-500 hover:text-zinc-900">Taxes</a>
        <a href="{{ url('/admin/settings/checkout') }}" wire:navigate class="border-b-2 border-transparent px-4 py-2 text-sm font-medium text-zinc-500 hover:text-zinc-900">Checkout</a>
        <a href="{{ url('/admin/settings/notifications') }}" wire:navigate class="border-b-2 border-transparent px-4 py-2 text-sm font-medium text-zinc-500 hover:text-zinc-900">Notifications</a>
    </nav>

    @if($activeTab === 'general')
        <form wire:submit="save" class="space-y-6">
            <x-admin.form-section title="Store details" description="Basic information about your store.">
                <div class="space-y-5"><flux:input wire:model="storeName" label="Store name" required /><flux:input wire:model="storeHandle" label="Store handle" disabled description="The store handle cannot be changed after creation." /></div>
            </x-admin.form-section>
            <x-admin.form-section title="Defaults" description="Currency, language, and timezone settings.">
                <div class="grid gap-5 sm:grid-cols-2">
                    <flux:select wire:model="defaultCurrency" label="Default currency"><flux:select.option value="EUR">EUR</flux:select.option><flux:select.option value="USD">USD</flux:select.option><flux:select.option value="GBP">GBP</flux:select.option><flux:select.option value="CHF">CHF</flux:select.option><flux:select.option value="CAD">CAD</flux:select.option><flux:select.option value="AUD">AUD</flux:select.option></flux:select>
                    <flux:select wire:model="defaultLocale" label="Default locale"><flux:select.option value="en">English</flux:select.option><flux:select.option value="de">German</flux:select.option><flux:select.option value="fr">French</flux:select.option><flux:select.option value="es">Spanish</flux:select.option><flux:select.option value="it">Italian</flux:select.option><flux:select.option value="nl">Dutch</flux:select.option></flux:select>
                    <div class="sm:col-span-2"><flux:select wire:model="timezone" label="Timezone">@foreach(timezone_identifiers_list() as $zone)<flux:select.option :value="$zone">{{ $zone }}</flux:select.option>@endforeach</flux:select></div>
                </div>
            </x-admin.form-section>
            <div class="flex justify-end"><flux:button type="submit" variant="primary" wire:loading.attr="disabled">Save</flux:button></div>
        </form>
    @else
        <x-admin.card title="Domains" description="Connect the hostnames that serve this store.">
            <x-slot:actions><flux:modal.trigger name="add-domain"><flux:button type="button" icon="plus">Add domain</flux:button></flux:modal.trigger></x-slot:actions>
            <x-admin.table-shell caption="Store domains">
                <x-slot:head><tr><th>Hostname</th><th>Type</th><th>Primary</th><th>TLS</th><th class="text-right">Actions</th></tr></x-slot:head>
                @forelse($this->domains as $domain)
                    <tr wire:key="domain-{{ $domain->id }}"><td class="font-medium">{{ $domain->hostname }}</td><td><x-admin.status-badge :status="$domain->type" :show-dot="false" /></td><td>@if($domain->is_primary)<x-admin.status-badge status="active" label="Primary" />@else<span class="text-zinc-400">—</span>@endif</td><td><x-admin.status-badge :status="$domain->tls_mode" :show-dot="false" /></td><td><div class="flex justify-end gap-1">@unless($domain->is_primary)<flux:button type="button" wire:click="setPrimary({{ $domain->id }})" variant="ghost" size="sm">Set primary</flux:button><flux:button type="button" wire:click="removeDomain({{ $domain->id }})" wire:confirm="Delete this domain?" variant="ghost" size="sm" icon="trash" aria-label="Delete {{ $domain->hostname }}" /></div>@endunless</td></tr>
                @empty<x-admin.table-empty colspan="5" title="No domains connected" />@endforelse
            </x-admin.table-shell>
        </x-admin.card>
    @endif

    <flux:modal name="add-domain" class="max-w-md"><form wire:submit="addDomain" class="space-y-6"><div><flux:heading size="lg">Add domain</flux:heading><flux:text class="mt-2">Enter a hostname without a protocol or path.</flux:text></div><flux:input wire:model="newHostname" label="Hostname" placeholder="shop.example.com" required /><flux:select wire:model="newType" label="Type"><flux:select.option value="storefront">Storefront</flux:select.option><flux:select.option value="admin">Admin</flux:select.option><flux:select.option value="api">API</flux:select.option></flux:select><div class="flex justify-end gap-2"><flux:modal.close><flux:button type="button" variant="ghost">Cancel</flux:button></flux:modal.close><flux:button type="submit" variant="primary">Add domain</flux:button></div></form></flux:modal>
</div>
