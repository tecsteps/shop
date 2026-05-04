<section class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">Store Settings</flux:heading>
            <flux:text class="mt-1">Store defaults, checkout preferences, and domains.</flux:text>
        </div>

        <div class="flex flex-wrap gap-2">
            <flux:button :href="route('admin.settings.index')" wire:navigate variant="primary">General</flux:button>
            <flux:button :href="route('admin.settings.shipping')" wire:navigate variant="filled">Shipping</flux:button>
            <flux:button :href="route('admin.settings.taxes')" wire:navigate variant="filled">Taxes</flux:button>
            <flux:button :href="route('admin.settings.checkout')" wire:navigate variant="filled">Checkout</flux:button>
            <flux:button :href="route('admin.settings.notifications')" wire:navigate variant="filled">Notifications</flux:button>
        </div>
    </div>

    @if (session('status'))
        <flux:callout color="green" icon="check-circle">{{ session('status') }}</flux:callout>
    @endif

    <form wire:submit="save" class="space-y-6">
        <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <div class="grid gap-6 p-5 lg:grid-cols-[260px_1fr]">
                <div>
                    <flux:heading size="lg">Store details</flux:heading>
                    <flux:text class="mt-1">Basic storefront identity.</flux:text>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:input wire:model="storeName" label="Store name" />
                    <flux:input wire:model="storeHandle" label="Store handle" disabled />
                    <flux:error name="storeName" />
                </div>
            </div>

            <flux:separator />

            <div class="grid gap-6 p-5 lg:grid-cols-[260px_1fr]">
                <div>
                    <flux:heading size="lg">Defaults</flux:heading>
                    <flux:text class="mt-1">Currency, language, and timezone.</flux:text>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <flux:select wire:model="defaultCurrency" label="Default currency">
                        @foreach ($currencyOptions as $currency)
                            <flux:select.option value="{{ $currency }}">{{ $currency }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="defaultLocale" label="Default locale">
                        @foreach ($localeOptions as $locale => $label)
                            <flux:select.option value="{{ $locale }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="timezone" label="Timezone">
                        @foreach ($timezoneOptions as $timezoneOption)
                            <flux:select.option value="{{ $timezoneOption }}">{{ $timezoneOption }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </div>

            <flux:separator />

            <div class="grid gap-6 p-5 lg:grid-cols-[260px_1fr]">
                <div>
                    <flux:heading size="lg">Checkout</flux:heading>
                    <flux:text class="mt-1">Customer-facing storefront defaults.</flux:text>
                </div>

                <div class="space-y-4">
                    <flux:switch wire:model="announcementEnabled" label="Announcement bar" align="left" />
                    <flux:input wire:model="announcementText" label="Announcement text" />
                    <flux:checkbox wire:model="guestCheckoutEnabled" label="Guest checkout enabled" />
                </div>
            </div>

            <div class="flex justify-end border-t border-zinc-200 p-5 dark:border-zinc-700">
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" data-test="settings-save-button">
                    <span wire:loading.remove>Save settings</span>
                    <span wire:loading>Saving...</span>
                </flux:button>
            </div>
        </div>
    </form>

    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex flex-col gap-4 border-b border-zinc-200 p-5 dark:border-zinc-700 lg:flex-row lg:items-end">
            <div class="flex-1">
                <flux:heading size="lg">Domains</flux:heading>
                <flux:text class="mt-1">Hostnames connected to this store.</flux:text>
            </div>

            <div class="grid flex-1 gap-3 sm:grid-cols-[1fr_160px_auto]">
                <flux:input wire:model="newHostname" label="Hostname" placeholder="shop.example.com" />
                <flux:select wire:model="newType" label="Type">
                    <flux:select.option value="storefront">Storefront</flux:select.option>
                    <flux:select.option value="admin">Admin</flux:select.option>
                    <flux:select.option value="api">API</flux:select.option>
                </flux:select>
                <div class="flex items-end">
                    <flux:button type="button" wire:click="addDomain" variant="primary" icon="plus" data-test="domain-add-button">Add</flux:button>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">Hostname</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Primary</th>
                        <th class="px-4 py-3">TLS</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @foreach ($domains as $domain)
                        <tr wire:key="domain-{{ $domain->getKey() }}">
                            <td class="px-4 py-3 font-medium text-zinc-950 dark:text-white">{{ $domain->hostname }}</td>
                            <td class="px-4 py-3"><flux:badge>{{ Str::headline($domain->type->value) }}</flux:badge></td>
                            <td class="px-4 py-3">
                                @if ($domain->is_primary)
                                    <flux:badge color="green">Primary</flux:badge>
                                @else
                                    <span class="text-zinc-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ Str::headline($domain->tls_mode) }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    @unless ($domain->is_primary)
                                        <flux:button type="button" size="sm" variant="filled" wire:click="setPrimary({{ $domain->getKey() }})">Set primary</flux:button>
                                    @endunless
                                    <flux:button type="button" size="sm" variant="danger" wire:click="removeDomain({{ $domain->getKey() }})">Delete</flux:button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>
