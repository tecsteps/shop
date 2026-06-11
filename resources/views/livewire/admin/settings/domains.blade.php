<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <flux:heading size="lg">{{ __('Domains') }}</flux:heading>

        @can('updateSettings', app('current_store'))
            <flux:modal.trigger name="add-domain">
                <flux:button variant="primary" icon="plus" data-test="add-domain-button">{{ __('Add domain') }}</flux:button>
            </flux:modal.trigger>
        @endcan
    </div>

    <x-admin.card class="!p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-left text-xs font-semibold text-zinc-500 uppercase dark:border-zinc-700 dark:text-zinc-400">
                        <th class="px-4 py-2.5">{{ __('Hostname') }}</th>
                        <th class="px-4 py-2.5">{{ __('Type') }}</th>
                        <th class="px-4 py-2.5">{{ __('Primary') }}</th>
                        <th class="px-4 py-2.5">{{ __('TLS') }}</th>
                        <th class="px-4 py-2.5 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($this->domains as $domain)
                        <tr wire:key="domain-{{ $domain->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">{{ $domain->hostname }}</td>
                            <td class="px-4 py-3">
                                <flux:badge size="sm" color="zinc">{{ $domain->type->value }}</flux:badge>
                            </td>
                            <td class="px-4 py-3">
                                @if ($domain->is_primary)
                                    <flux:badge size="sm" color="green">{{ __('Primary') }}</flux:badge>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <flux:badge size="sm" color="blue">{{ $domain->tls_mode }}</flux:badge>
                            </td>
                            <td class="px-4 py-3">
                                @can('updateSettings', app('current_store'))
                                    <div class="flex justify-end gap-2">
                                        @unless ($domain->is_primary)
                                            <flux:button variant="ghost" size="sm" wire:click="setPrimary({{ $domain->id }})" data-test="set-primary-{{ $domain->id }}">
                                                {{ __('Set Primary') }}
                                            </flux:button>
                                        @endunless
                                        <flux:button
                                            variant="ghost"
                                            size="sm"
                                            icon="trash"
                                            wire:click="removeDomain({{ $domain->id }})"
                                            wire:confirm="{{ __('Remove this domain?') }}"
                                            aria-label="{{ __('Remove :hostname', ['hostname' => $domain->hostname]) }}"
                                            data-test="remove-domain-{{ $domain->id }}"
                                        />
                                    </div>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center">
                                <flux:text>{{ __('No domains configured yet.') }}</flux:text>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.card>

    <flux:modal name="add-domain" class="md:max-w-md">
        <form wire:submit="addDomain" class="space-y-4">
            <flux:heading size="lg">{{ __('Add domain') }}</flux:heading>

            <flux:field>
                <flux:label>{{ __('Hostname') }}</flux:label>
                <flux:input wire:model="newHostname" placeholder="shop.example.com" data-test="new-hostname-input" />
                <flux:error name="newHostname" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Type') }}</flux:label>
                <flux:select wire:model="newType" data-test="new-domain-type-select">
                    <flux:select.option value="storefront">{{ __('Storefront') }}</flux:select.option>
                    <flux:select.option value="admin">{{ __('Admin') }}</flux:select.option>
                    <flux:select.option value="api">{{ __('API') }}</flux:select.option>
                </flux:select>
                <flux:error name="newType" />
            </flux:field>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" data-test="confirm-add-domain-button">
                    {{ __('Add domain') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
