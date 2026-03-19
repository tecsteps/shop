<div>
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Settings') }}</flux:heading>
    </div>

    <div class="flex gap-4 mb-6">
        <flux:button :href="route('admin.settings.index')" :variant="request()->routeIs('admin.settings.index') ? 'primary' : 'ghost'" wire:navigate>
            {{ __('General') }}
        </flux:button>
        <flux:button :href="route('admin.settings.shipping')" :variant="request()->routeIs('admin.settings.shipping') ? 'primary' : 'ghost'" wire:navigate>
            {{ __('Shipping') }}
        </flux:button>
        <flux:button :href="route('admin.settings.taxes')" :variant="request()->routeIs('admin.settings.taxes') ? 'primary' : 'ghost'" wire:navigate>
            {{ __('Taxes') }}
        </flux:button>
    </div>

    <form wire:submit="save">
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6 space-y-4">
            <flux:heading size="md">{{ __('General') }}</flux:heading>

            <flux:field>
                <flux:label>{{ __('Store name') }}</flux:label>
                <flux:input wire:model="storeName" />
                <flux:error name="storeName" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Default currency') }}</flux:label>
                <flux:input wire:model="defaultCurrency" maxlength="3" placeholder="EUR" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Timezone') }}</flux:label>
                <flux:input wire:model="timezone" placeholder="UTC" />
            </flux:field>
        </div>

        <div class="mt-6 flex justify-end">
            <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
        </div>
    </form>

    {{-- Domains --}}
    <div class="mt-8 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6 space-y-4">
        <flux:heading size="md">{{ __('Domains') }}</flux:heading>

        @if($domains->count() > 0)
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                        <th class="p-2 text-left font-medium text-zinc-500">{{ __('Hostname') }}</th>
                        <th class="p-2 text-left font-medium text-zinc-500">{{ __('Type') }}</th>
                        <th class="p-2 text-left font-medium text-zinc-500">{{ __('Primary') }}</th>
                        <th class="p-2 text-right font-medium text-zinc-500"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($domains as $domain)
                        <tr class="border-b border-zinc-100 dark:border-zinc-800">
                            <td class="p-2">{{ $domain->hostname }}</td>
                            <td class="p-2">{{ ucfirst($domain->type->value) }}</td>
                            <td class="p-2">
                                @if($domain->is_primary)
                                    <flux:badge size="sm" color="green">{{ __('Primary') }}</flux:badge>
                                @endif
                            </td>
                            <td class="p-2 text-right">
                                @if(!$domain->is_primary)
                                    <flux:button size="sm" variant="ghost" wire:click="removeDomain({{ $domain->id }})" wire:confirm="{{ __('Remove this domain?') }}">
                                        {{ __('Remove') }}
                                    </flux:button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <flux:text class="text-zinc-500">{{ __('No domains configured.') }}</flux:text>
        @endif

        <div class="flex gap-2">
            <flux:input wire:model="newDomainHostname" placeholder="{{ __('example.com') }}" class="flex-1" />
            <flux:button wire:click="addDomain">{{ __('Add domain') }}</flux:button>
        </div>
        <flux:error name="newDomainHostname" />
    </div>
</div>
