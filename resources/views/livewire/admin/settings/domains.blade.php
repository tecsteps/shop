<div>
    <div class="mb-4 flex items-center justify-between">
        <flux:heading size="lg">{{ __('Domains') }}</flux:heading>
        <flux:button variant="primary" icon="plus" wire:click="$set('showAddModal', true)" data-test="add-domain">{{ __('Add domain') }}</flux:button>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Hostname') }}</flux:table.column>
            <flux:table.column>{{ __('Type') }}</flux:table.column>
            <flux:table.column>{{ __('Primary') }}</flux:table.column>
            <flux:table.column>{{ __('TLS') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @foreach ($this->domains as $domain)
                <flux:table.row :key="'domain-'.$domain->id">
                    <flux:table.cell variant="strong">{{ $domain->hostname }}</flux:table.cell>
                    <flux:table.cell><flux:badge size="sm" color="zinc">{{ $domain->type->value }}</flux:badge></flux:table.cell>
                    <flux:table.cell>
                        @if ($domain->is_primary)<flux:badge size="sm" color="green">{{ __('Primary') }}</flux:badge>@endif
                    </flux:table.cell>
                    <flux:table.cell><flux:badge size="sm" color="zinc">{{ $domain->tls_mode }}</flux:badge></flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-end gap-2">
                            @unless ($domain->is_primary)
                                <flux:button size="sm" variant="ghost" wire:click="setPrimary({{ $domain->id }})">{{ __('Set Primary') }}</flux:button>
                            @endunless
                            <flux:button size="sm" variant="ghost" icon="trash" wire:click="removeDomain({{ $domain->id }})" wire:confirm="{{ __('Remove this domain?') }}" :aria-label="__('Delete')" />
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <flux:modal wire:model.self="showAddModal" name="add-domain" class="md:w-96">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Add domain') }}</flux:heading>
            <flux:field>
                <flux:label>{{ __('Hostname') }}</flux:label>
                <flux:input wire:model="newHostname" placeholder="shop.example.com" data-test="new-hostname" />
                <flux:error name="newHostname" />
            </flux:field>
            <flux:field>
                <flux:label>{{ __('Type') }}</flux:label>
                <flux:select wire:model="newType">
                    <flux:select.option value="storefront">{{ __('Storefront') }}</flux:select.option>
                    <flux:select.option value="admin">{{ __('Admin') }}</flux:select.option>
                    <flux:select.option value="api">{{ __('API') }}</flux:select.option>
                </flux:select>
            </flux:field>
            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="$set('showAddModal', false)">{{ __('Cancel') }}</flux:button>
                <flux:button variant="primary" wire:click="addDomain" data-test="submit-domain">{{ __('Add domain') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
