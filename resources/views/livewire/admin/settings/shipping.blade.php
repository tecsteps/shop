<section class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">Shipping</flux:heading>
            <flux:text class="mt-1">Zones, rates, and address matching.</flux:text>
        </div>

        <div class="flex flex-wrap gap-2">
            <flux:button :href="route('admin.settings.index')" wire:navigate variant="filled">General</flux:button>
            <flux:button :href="route('admin.settings.shipping')" wire:navigate variant="primary">Shipping</flux:button>
            <flux:button :href="route('admin.settings.taxes')" wire:navigate variant="filled">Taxes</flux:button>
            <flux:button :href="route('admin.settings.checkout')" wire:navigate variant="filled">Checkout</flux:button>
            <flux:button :href="route('admin.settings.notifications')" wire:navigate variant="filled">Notifications</flux:button>
        </div>
    </div>

    @if (session('status'))
        <flux:callout color="green" icon="check-circle">{{ session('status') }}</flux:callout>
    @endif

    <div class="grid gap-6 xl:grid-cols-[1fr_360px]">
        <div class="space-y-4">
            @foreach ($zones as $zone)
                <div wire:key="shipping-zone-{{ $zone->getKey() }}" class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900" data-test="shipping-zone-{{ Str::slug($zone->name) }}">
                    <div class="flex flex-col gap-3 border-b border-zinc-200 p-5 dark:border-zinc-700 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <flux:heading size="lg">{{ $zone->name }}</flux:heading>
                            <flux:text class="mt-1">{{ implode(', ', $zone->countries_json ?? []) }}</flux:text>
                        </div>

                        <div class="flex gap-2">
                            <flux:button type="button" wire:click="editZone({{ $zone->getKey() }})" size="sm" variant="filled">Edit</flux:button>
                            <flux:button type="button" wire:click="deleteZone({{ $zone->getKey() }})" wire:confirm="Delete this shipping zone?" size="sm" variant="danger">Delete</flux:button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400">
                                <tr>
                                    <th class="px-4 py-3">Name</th>
                                    <th class="px-4 py-3">Type</th>
                                    <th class="px-4 py-3">Config</th>
                                    <th class="px-4 py-3">Active</th>
                                    <th class="px-4 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                                @forelse ($zone->rates as $rate)
                                    <tr wire:key="shipping-rate-{{ $rate->getKey() }}">
                                        <td class="px-4 py-3 font-medium text-zinc-950 dark:text-white">{{ $rate->name }}</td>
                                        <td class="px-4 py-3"><flux:badge>{{ Str::headline($rate->type->value) }}</flux:badge></td>
                                        <td class="px-4 py-3">{{ $this->rateSummary($rate) }}</td>
                                        <td class="px-4 py-3">
                                            <flux:switch wire:click="toggleRateActive({{ $rate->getKey() }})" :checked="$rate->is_active" aria-label="Toggle {{ $rate->name }}" />
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex justify-end gap-2">
                                                <flux:button type="button" wire:click="editRate({{ $rate->getKey() }})" size="sm" variant="filled">Edit</flux:button>
                                                <flux:button type="button" wire:click="deleteRate({{ $rate->getKey() }})" wire:confirm="Delete this shipping rate?" size="sm" variant="danger">Delete</flux:button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-8 text-center text-zinc-500">No rates configured.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="border-t border-zinc-200 p-4 dark:border-zinc-700">
                        <flux:button type="button" wire:click="addRate({{ $zone->getKey() }})" variant="filled" icon="plus" data-test="add-rate-{{ Str::slug($zone->name) }}">Add rate</flux:button>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="space-y-6">
            <form wire:submit="saveZone" class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">{{ $editingZoneId ? 'Edit zone' : 'Add zone' }}</flux:heading>

                <div class="mt-4 space-y-4">
                    <flux:input wire:model="zoneName" label="Zone name" placeholder="Germany" />
                    <flux:error name="zoneName" />

                    <flux:input wire:model="zoneCountries" label="Countries" placeholder="DE, AT, CH" />
                    <flux:error name="zoneCountries" />

                    <div class="flex justify-end gap-2">
                        <flux:button type="button" wire:click="$set('editingZoneId', null)" variant="ghost">Cancel</flux:button>
                        <flux:button type="submit" variant="primary">Save zone</flux:button>
                    </div>
                </div>
            </form>

            @if ($rateZoneId)
                <form wire:submit="saveRate" class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:heading size="lg">{{ $editingRateId ? 'Edit rate' : 'Add rate' }}</flux:heading>

                    <div class="mt-4 space-y-4">
                        <flux:input wire:model="rateName" label="Rate name" placeholder="Standard" />
                        <flux:select wire:model.live="rateType" label="Rate type">
                            <flux:select.option value="flat">Flat</flux:select.option>
                            <flux:select.option value="weight">Weight</flux:select.option>
                            <flux:select.option value="price">Price</flux:select.option>
                            <flux:select.option value="carrier">Carrier</flux:select.option>
                        </flux:select>
                        <flux:input wire:model="rateAmount" type="number" step="0.01" min="0" label="Amount" />

                        @if ($rateType === 'weight')
                            <div class="grid gap-3 sm:grid-cols-2">
                                <flux:input wire:model="minimumWeight" type="number" min="0" label="Min weight (g)" />
                                <flux:input wire:model="maximumWeight" type="number" min="1" label="Max weight (g)" />
                            </div>
                        @endif

                        @if ($rateType === 'price')
                            <div class="grid gap-3 sm:grid-cols-2">
                                <flux:input wire:model="minimumOrderAmount" type="number" step="0.01" min="0" label="Min order" />
                                <flux:input wire:model="maximumOrderAmount" type="number" step="0.01" min="0" label="Max order" />
                            </div>
                        @endif

                        <flux:switch wire:model="rateActive" label="{{ $rateActive ? 'Active' : 'Inactive' }}" align="left" />

                        <div class="flex justify-end gap-2">
                            <flux:button type="button" wire:click="$set('rateZoneId', null)" variant="ghost">Cancel</flux:button>
                            <flux:button type="submit" variant="primary" data-test="shipping-rate-save-button">Save rate</flux:button>
                        </div>
                    </div>
                </form>
            @endif

            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">Test address</flux:heading>

                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <flux:input wire:model="testCountry" label="Country" placeholder="DE" />
                    <flux:input wire:model="testRegion" label="Region" placeholder="DE-BE" />
                </div>

                <div class="mt-4">
                    <flux:button type="button" wire:click="testShippingAddress" variant="filled">Test</flux:button>
                </div>

                @if ($testResult)
                    <div class="mt-4 rounded-lg bg-zinc-50 p-3 text-sm dark:bg-zinc-800">
                        @if ($testResult['zone'])
                            <div class="font-medium text-zinc-950 dark:text-white">Matched zone: {{ $testResult['zone'] }}</div>
                            <ul class="mt-2 space-y-1 text-zinc-600 dark:text-zinc-300">
                                @foreach ($testResult['rates'] as $rate)
                                    <li>{{ $rate }}</li>
                                @endforeach
                            </ul>
                        @else
                            <div class="text-amber-700 dark:text-amber-300">No shipping zone matches this address.</div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
