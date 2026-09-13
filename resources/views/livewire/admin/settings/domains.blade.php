<div>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="lg">Domains</flux:heading>
        <flux:button variant="primary" wire:click="$set('showAddModal', true)">Add domain</flux:button>
    </div>

    <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                    <th class="px-4 py-3 font-medium">Hostname</th>
                    <th class="px-4 py-3 font-medium">Type</th>
                    <th class="px-4 py-3 font-medium">Primary</th>
                    <th class="px-4 py-3 font-medium">TLS</th>
                    <th class="px-4 py-3 font-medium"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($domains as $domain)
                    <tr class="border-b border-zinc-100 dark:border-zinc-800" wire:key="domain-{{ $domain->getKey() }}">
                        <td class="px-4 py-3">{{ $domain->hostname }}</td>
                        <td class="px-4 py-3"><flux:badge>{{ $domain->type->value }}</flux:badge></td>
                        <td class="px-4 py-3">@if ($domain->is_primary)<flux:badge color="green">Primary</flux:badge>@endif</td>
                        <td class="px-4 py-3"><flux:badge>{{ $domain->tls_mode->value }}</flux:badge></td>
                        <td class="px-4 py-3 text-right">
                            @if (! $domain->is_primary)
                                <flux:button variant="ghost" size="sm" wire:click="setPrimary({{ $domain->getKey() }})">Set Primary</flux:button>
                                <flux:button variant="ghost" size="sm" wire:click="removeDomain({{ $domain->getKey() }})" aria-label="Delete domain">
                                    <flux:icon.trash class="size-4" variant="outline" />
                                </flux:button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center"><flux:text variant="subtle">No domains configured.</flux:text></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <flux:modal wire:model="showAddModal" class="max-w-md">
        <flux:heading size="lg">Add domain</flux:heading>
        <flux:input wire:model="newHostname" label="Hostname" placeholder="shop.example.com" class="mt-4" />
        <flux:select wire:model="newType" label="Type" class="mt-3">
            <flux:select.option value="storefront">Storefront</flux:select.option>
            <flux:select.option value="admin">Admin</flux:select.option>
            <flux:select.option value="api">API</flux:select.option>
        </flux:select>
        <div class="mt-4 flex justify-end gap-2">
            <flux:button variant="ghost" wire:click="$set('showAddModal', false)">Cancel</flux:button>
            <flux:button variant="primary" wire:click="addDomain">Add domain</flux:button>
        </div>
    </flux:modal>
</div>
