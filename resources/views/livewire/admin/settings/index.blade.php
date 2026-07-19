<div class="space-y-6">
    <flux:heading size="xl">Settings</flux:heading>

    {{-- Tabs (spec 03 §11.2). Shipping and Taxes have dedicated pages. --}}
    <div class="flex flex-wrap gap-1 border-b border-zinc-200 dark:border-zinc-700" role="tablist">
        @foreach (['general' => 'General', 'domains' => 'Domains', 'checkout' => 'Checkout', 'notifications' => 'Notifications'] as $key => $label)
            <button
                type="button"
                role="tab"
                aria-selected="{{ $tab === $key ? 'true' : 'false' }}"
                wire:click="setTab('{{ $key }}')"
                class="-mb-px border-b-2 px-4 py-2 text-sm font-medium {{ $tab === $key ? 'border-blue-600 text-zinc-900 dark:border-blue-400 dark:text-zinc-100' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}"
            >{{ $label }}</button>
        @endforeach
        <a href="{{ route('admin.settings.shipping') }}" wire:navigate class="-mb-px border-b-2 border-transparent px-4 py-2 text-sm font-medium text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300">Shipping</a>
        <a href="{{ route('admin.settings.taxes') }}" wire:navigate class="-mb-px border-b-2 border-transparent px-4 py-2 text-sm font-medium text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300">Taxes</a>
    </div>

    {{-- General tab (spec 03 §11.1) --}}
    @if ($tab === 'general')
        <div class="space-y-6">
            <div class="grid gap-6 lg:grid-cols-3">
                <div>
                    <flux:heading size="md">Store details</flux:heading>
                    <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Basic information about your store.</flux:text>
                </div>
                <div class="rounded-lg border border-zinc-200 bg-white p-6 lg:col-span-2 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:field>
                        <flux:label for="storeName">Store name</flux:label>
                        <flux:input id="storeName" wire:model.blur="storeName" />
                        <flux:error name="storeName" />
                    </flux:field>

                    <flux:field class="mt-4">
                        <flux:label for="contactEmail">Contact email</flux:label>
                        <flux:input id="contactEmail" type="email" wire:model.blur="contactEmail" placeholder="hello@example.com" />
                        <flux:error name="contactEmail" />
                    </flux:field>
                </div>
            </div>

            <flux:separator />

            <div class="grid gap-6 lg:grid-cols-3">
                <div>
                    <flux:heading size="md">Defaults</flux:heading>
                    <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Currency, language, and timezone settings.</flux:text>
                </div>
                <div class="rounded-lg border border-zinc-200 bg-white p-6 lg:col-span-2 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:field>
                        <flux:label for="defaultCurrency">Default currency</flux:label>
                        <flux:select id="defaultCurrency" wire:model="defaultCurrency">
                            @foreach (['EUR', 'USD', 'GBP', 'CHF', 'SEK', 'PLN'] as $currency)
                                <flux:select.option value="{{ $currency }}">{{ $currency }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="defaultCurrency" />
                    </flux:field>

                    <flux:field class="mt-4">
                        <flux:label for="defaultLocale">Default locale</flux:label>
                        <flux:select id="defaultLocale" wire:model="defaultLocale">
                            <flux:select.option value="en">English</flux:select.option>
                            <flux:select.option value="de">German</flux:select.option>
                            <flux:select.option value="fr">French</flux:select.option>
                        </flux:select>
                        <flux:error name="defaultLocale" />
                    </flux:field>

                    <flux:field class="mt-4">
                        <flux:label for="timezone">Timezone</flux:label>
                        <flux:select id="timezone" wire:model="timezone">
                            @foreach ($timezones as $tz)
                                <flux:select.option value="{{ $tz }}">{{ $tz }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="timezone" />
                    </flux:field>
                </div>
            </div>

            <div class="flex justify-end">
                <flux:button variant="primary" wire:click="saveGeneral" wire:loading.attr="disabled" wire:target="saveGeneral">Save</flux:button>
            </div>
        </div>
    @endif

    {{-- Domains tab (spec 03 §11.2) --}}
    @if ($tab === 'domains')
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <flux:heading size="md">Domains</flux:heading>
                <flux:button variant="primary" icon="plus" wire:click="$set('showDomainForm', true)">Add domain</flux:button>
            </div>

            <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <table class="w-full min-w-[640px] text-left text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 text-xs tracking-wider text-zinc-500 uppercase dark:border-zinc-700 dark:text-zinc-400">
                            <th class="px-4 py-3 font-medium">Hostname</th>
                            <th class="px-4 py-3 font-medium">Type</th>
                            <th class="px-4 py-3 font-medium">Primary</th>
                            <th class="px-4 py-3 font-medium">TLS</th>
                            <th class="px-4 py-3 font-medium"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($domains as $domain)
                            <tr wire:key="domain-{{ $domain->id }}">
                                <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">{{ $domain->hostname }}</td>
                                <td class="px-4 py-3"><flux:badge size="sm">{{ ucfirst($domain->type->value) }}</flux:badge></td>
                                <td class="px-4 py-3">
                                    @if ($domain->is_primary)
                                        <flux:badge size="sm" color="green">Primary</flux:badge>
                                    @endif
                                </td>
                                <td class="px-4 py-3"><flux:badge size="sm" color="zinc">{{ $domain->tls_mode }}</flux:badge></td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        @if (! $domain->is_primary)
                                            <flux:button size="sm" variant="ghost" wire:click="setPrimary({{ $domain->id }})">Set primary</flux:button>
                                            <flux:button size="sm" variant="ghost" icon="trash" wire:click="removeDomain({{ $domain->id }})" aria-label="Remove {{ $domain->hostname }}" />
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Add domain modal (spec 03 §11.2) --}}
        <flux:modal wire:model="showDomainForm" name="add-domain" class="max-w-md">
            <div class="space-y-4">
                <flux:heading size="lg">Add domain</flux:heading>

                <flux:field>
                    <flux:label for="newHostname">Hostname</flux:label>
                    <flux:input id="newHostname" wire:model.blur="newHostname" placeholder="shop.example.com" />
                    <flux:error name="newHostname" />
                </flux:field>

                <flux:field>
                    <flux:label for="newType">Type</flux:label>
                    <flux:select id="newType" wire:model="newType">
                        <flux:select.option value="storefront">Storefront</flux:select.option>
                        <flux:select.option value="admin">Admin</flux:select.option>
                        <flux:select.option value="api">API</flux:select.option>
                    </flux:select>
                    <flux:error name="newType" />
                </flux:field>

                <div class="flex justify-end gap-2">
                    <flux:button variant="ghost" wire:click="$set('showDomainForm', false)">Cancel</flux:button>
                    <flux:button variant="primary" wire:click="addDomain">Add domain</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif

    {{-- Checkout tab --}}
    @if ($tab === 'checkout')
        <div class="grid gap-6 lg:grid-cols-3">
            <div>
                <flux:heading size="md">Checkout</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Order numbering and checkout lifecycle settings.</flux:text>
            </div>
            <div class="rounded-lg border border-zinc-200 bg-white p-6 lg:col-span-2 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label for="orderNumberPrefix">Order number prefix</flux:label>
                        <flux:input id="orderNumberPrefix" wire:model.blur="orderNumberPrefix" placeholder="#" />
                        <flux:error name="orderNumberPrefix" />
                    </flux:field>

                    <flux:field>
                        <flux:label for="orderNumberStart">Order number start</flux:label>
                        <flux:input id="orderNumberStart" type="number" min="1" wire:model.blur="orderNumberStart" />
                        <flux:error name="orderNumberStart" />
                    </flux:field>

                    <flux:field>
                        <flux:label for="bankTransferCancelDays">Bank transfer cancel days</flux:label>
                        <flux:input id="bankTransferCancelDays" type="number" min="1" max="90" wire:model.blur="bankTransferCancelDays" />
                        <flux:description>Days before unpaid bank transfer orders are cancelled.</flux:description>
                        <flux:error name="bankTransferCancelDays" />
                    </flux:field>

                    <flux:field>
                        <flux:label for="cartAbandonDays">Abandoned cart days</flux:label>
                        <flux:input id="cartAbandonDays" type="number" min="1" max="365" wire:model.blur="cartAbandonDays" />
                        <flux:description>Days of inactivity before a cart is marked abandoned.</flux:description>
                        <flux:error name="cartAbandonDays" />
                    </flux:field>
                </div>

                <div class="mt-6 flex justify-end">
                    <flux:button variant="primary" wire:click="saveCheckout" wire:loading.attr="disabled" wire:target="saveCheckout">Save</flux:button>
                </div>
            </div>
        </div>
    @endif

    {{-- Notifications tab --}}
    @if ($tab === 'notifications')
        <div class="grid gap-6 lg:grid-cols-3">
            <div>
                <flux:heading size="md">Notifications</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Email notifications sent by your store.</flux:text>
            </div>
            <div class="space-y-4 rounded-lg border border-zinc-200 bg-white p-6 lg:col-span-2 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:switch wire:model="orderConfirmationEmail" label="Order confirmation email" description="Sent to customers after an order is placed." />
                <flux:switch wire:model="shippingEmail" label="Shipping email" description="Sent when a fulfillment is shipped." />
                <flux:switch wire:model="marketingEmail" label="Marketing emails" description="Send promotional emails to customers." />

                <div class="flex justify-end pt-2">
                    <flux:button variant="primary" wire:click="saveNotifications" wire:loading.attr="disabled" wire:target="saveNotifications">Save</flux:button>
                </div>
            </div>
        </div>
    @endif
</div>
