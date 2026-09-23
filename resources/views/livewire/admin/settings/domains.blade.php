<section aria-labelledby="domains-heading" class="max-w-5xl">
    <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div><h2 id="domains-heading" class="text-xl font-semibold tracking-tight">Domains</h2><p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Manage storefront, admin, and API hostnames for this store.</p></div>
        <flux:button type="button" variant="primary" icon="plus" wire:click="openAddDomainModal">Add domain</flux:button>
    </div>

    <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        @if ($domains->isEmpty())
            <div class="px-6 py-12 text-center"><span class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400"><flux:icon name="globe-alt" class="size-6" /></span><h3 class="mt-4 font-semibold">No domains added</h3><p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Add a hostname to make this store available on your own domain.</p><button type="button" wire:click="openAddDomainModal" class="mt-4 inline-flex min-h-10 items-center gap-2 rounded-lg px-3 text-sm font-semibold text-blue-700 hover:bg-blue-50 focus-visible:outline-2 focus-visible:outline-blue-600 dark:text-blue-300 dark:hover:bg-blue-950/40"><flux:icon name="plus" class="size-4" />Add your first domain</button></div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[42rem] divide-y divide-zinc-200 text-left dark:divide-zinc-800">
                    <caption class="sr-only">Domains connected to this store</caption>
                    <thead class="bg-zinc-50 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:bg-zinc-950/50 dark:text-zinc-400"><tr><th scope="col" class="px-5 py-3">Hostname</th><th scope="col" class="px-5 py-3">Type</th><th scope="col" class="px-5 py-3">Primary</th><th scope="col" class="px-5 py-3">TLS</th><th scope="col" class="px-5 py-3 text-right">Actions</th></tr></thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($domains as $domain)
                            <tr wire:key="store-domain-{{ $domain->id }}" class="align-middle"><td class="px-5 py-4"><span class="font-medium">{{ $domain->hostname }}</span></td><td class="px-5 py-4"><span class="inline-flex rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium capitalize text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">{{ $domain->type }}</span></td><td class="px-5 py-4">@if ($domain->is_primary)<span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">Primary</span>@else<span class="text-sm text-zinc-500 dark:text-zinc-400">—</span>@endif</td><td class="px-5 py-4"><span class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium capitalize text-blue-800 dark:bg-blue-950 dark:text-blue-200">{{ $domain->tls_mode }}</span></td><td class="px-5 py-4"><div class="flex justify-end gap-2">@if (! $domain->is_primary)<flux:button size="sm" variant="ghost" wire:click="setPrimary({{ $domain->id }})" aria-label="Set {{ $domain->hostname }} as primary">Set primary</flux:button>@endif<flux:button size="sm" variant="danger" wire:click="removeDomain({{ $domain->id }})" wire:confirm="Remove {{ $domain->hostname }} from this store?" aria-label="Remove {{ $domain->hostname }}">Remove</flux:button></div></td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        <p class="border-t border-zinc-200 px-5 py-3 text-xs text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">Managed TLS is enabled for new domains. Domain DNS configuration may be required before the hostname becomes reachable.</p>
    </div>

    <flux:modal name="add-domain" wire:model="showAddDomainModal" class="max-w-md">
        <form wire:submit="addDomain" class="grid gap-5">
            <div><flux:heading size="lg">Add domain</flux:heading><flux:subheading class="mt-1">Connect a hostname to this store.</flux:subheading></div>
            <flux:field>
                <flux:label for="domain-hostname">Hostname</flux:label>
                <flux:input id="domain-hostname" type="text" wire:model="newHostname" placeholder="shop.example.com" autocomplete="off" autocapitalize="none" spellcheck="false" />
                <flux:error name="newHostname" />
            </flux:field>
            <flux:field>
                <flux:label for="domain-type">Type</flux:label>
                <select id="domain-type" wire:model="newType" class="min-h-11 w-full rounded-xl border border-zinc-300 bg-white px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-950"><option value="storefront">Storefront</option><option value="admin">Admin</option><option value="api">API</option></select>
                <flux:error name="newType" />
            </flux:field>
            <div class="flex justify-end gap-2"><flux:modal.close><flux:button type="button" variant="ghost">Cancel</flux:button></flux:modal.close><flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="addDomain">Add domain</flux:button></div>
        </form>
    </flux:modal>
</section>
