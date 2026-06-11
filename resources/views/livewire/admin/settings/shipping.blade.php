<div class="space-y-6">
    <x-admin.breadcrumbs :items="[
        ['label' => __('Settings'), 'href' => route('admin.settings.index')],
        ['label' => __('Shipping')],
    ]" />

    <flux:heading size="xl" level="1">{{ __('Settings') }}</flux:heading>

    <x-admin.settings-tabs active="shipping" />

    <div class="flex flex-wrap items-center justify-between gap-3">
        <flux:heading size="lg">{{ __('Shipping') }}</flux:heading>

        @can('updateSettings', app('current_store'))
            <flux:button variant="primary" icon="plus" wire:click="openZoneModal" data-test="add-zone-button">
                {{ __('Add zone') }}
            </flux:button>
        @endcan
    </div>

    @if ($this->zones->isEmpty())
        <x-admin.card class="flex flex-col items-center gap-3 py-16 text-center">
            <flux:icon name="truck" class="size-12 text-zinc-300 dark:text-zinc-600" />
            <flux:heading size="lg">{{ __('No shipping zones yet') }}</flux:heading>
            <flux:text>{{ __('Create a shipping zone to define where you ship and what it costs.') }}</flux:text>
        </x-admin.card>
    @else
        @foreach ($this->zones as $zone)
            <x-admin.card wire:key="zone-{{ $zone->id }}" class="space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <flux:heading>{{ $zone->name }}</flux:heading>
                        <flux:text class="mt-1 text-sm">
                            {{ __('Countries:') }} {{ implode(', ', $zone->countries_json ?? []) }}
                            @if (filled($zone->regions_json))
                                - {{ __('Regions:') }} {{ implode(', ', $zone->regions_json) }}
                            @endif
                        </flux:text>
                    </div>

                    @can('updateSettings', app('current_store'))
                        <div class="flex gap-2">
                            <flux:button variant="ghost" size="sm" wire:click="openZoneModal({{ $zone->id }})" data-test="edit-zone-{{ $zone->id }}">
                                {{ __('Edit') }}
                            </flux:button>
                            <flux:button
                                variant="ghost"
                                size="sm"
                                icon="trash"
                                wire:click="deleteZone({{ $zone->id }})"
                                wire:confirm="{{ __('Delete this zone and all of its rates?') }}"
                                aria-label="{{ __('Delete zone :name', ['name' => $zone->name]) }}"
                                data-test="delete-zone-{{ $zone->id }}"
                            />
                        </div>
                    @endcan
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-zinc-200 text-left text-xs font-semibold text-zinc-500 uppercase dark:border-zinc-700 dark:text-zinc-400">
                                <th class="px-2 py-2">{{ __('Name') }}</th>
                                <th class="px-2 py-2">{{ __('Type') }}</th>
                                <th class="px-2 py-2">{{ __('Config') }}</th>
                                <th class="px-2 py-2">{{ __('Active') }}</th>
                                <th class="px-2 py-2 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @forelse ($zone->rates as $rate)
                                <tr wire:key="rate-{{ $rate->id }}">
                                    <td class="px-2 py-2.5 font-medium text-zinc-900 dark:text-white">{{ $rate->name }}</td>
                                    <td class="px-2 py-2.5">
                                        <flux:badge size="sm" color="zinc">{{ $rate->type->value }}</flux:badge>
                                    </td>
                                    <td class="px-2 py-2.5 text-zinc-600 dark:text-zinc-400">{{ $this->describeRateConfig($rate) }}</td>
                                    <td class="px-2 py-2.5">
                                        @can('updateSettings', app('current_store'))
                                            <flux:switch
                                                :checked="$rate->is_active"
                                                wire:click="toggleRateActive({{ $rate->id }})"
                                                aria-label="{{ __('Toggle :name', ['name' => $rate->name]) }}"
                                                data-test="toggle-rate-{{ $rate->id }}"
                                            />
                                        @else
                                            <x-admin.status-badge :status="$rate->is_active ? 'active' : 'disabled'" />
                                        @endcan
                                    </td>
                                    <td class="px-2 py-2.5">
                                        @can('updateSettings', app('current_store'))
                                            <div class="flex justify-end gap-2">
                                                <flux:button variant="ghost" size="sm" wire:click="openRateModal({{ $zone->id }}, {{ $rate->id }})" data-test="edit-rate-{{ $rate->id }}">
                                                    {{ __('Edit') }}
                                                </flux:button>
                                                <flux:button
                                                    variant="ghost"
                                                    size="sm"
                                                    icon="trash"
                                                    wire:click="deleteRate({{ $rate->id }})"
                                                    wire:confirm="{{ __('Delete this rate?') }}"
                                                    aria-label="{{ __('Delete rate :name', ['name' => $rate->name]) }}"
                                                    data-test="delete-rate-{{ $rate->id }}"
                                                />
                                            </div>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-2 py-4 text-center">
                                        <flux:text>{{ __('No rates in this zone yet.') }}</flux:text>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @can('updateSettings', app('current_store'))
                    <flux:button variant="ghost" size="sm" icon="plus" wire:click="openRateModal({{ $zone->id }})" data-test="add-rate-{{ $zone->id }}">
                        {{ __('Add rate') }}
                    </flux:button>
                @endcan
            </x-admin.card>
        @endforeach
    @endif

    {{-- Test shipping address tool --}}
    <x-admin.card :heading="__('Test shipping address')" class="space-y-4">
        <flux:text>{{ __('Enter an address to see which shipping zone and rates match.') }}</flux:text>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <flux:field>
                <flux:label>{{ __('Country') }}</flux:label>
                <flux:select wire:model="testAddress.country_code" data-test="test-country-select">
                    @foreach (\App\Livewire\Admin\Settings\Shipping::COUNTRIES as $code => $name)
                        <flux:select.option value="{{ $code }}">{{ $name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>{{ __('State / Region') }}</flux:label>
                <flux:input wire:model="testAddress.province_code" placeholder="CA" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('City') }}</flux:label>
                <flux:input wire:model="testAddress.city" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('ZIP / Postal code') }}</flux:label>
                <flux:input wire:model="testAddress.postal_code" />
            </flux:field>
        </div>

        <flux:button wire:click="testShippingAddress" data-test="test-address-button">{{ __('Test') }}</flux:button>

        @if ($testResult === false)
            <flux:callout variant="warning" icon="exclamation-triangle">
                <flux:callout.text>{{ __('No shipping zone matches this address.') }}</flux:callout.text>
            </flux:callout>
        @elseif (is_array($testResult))
            <flux:callout variant="success" icon="check-circle" data-test="test-address-result">
                <flux:callout.text>
                    {{ __('Matched zone: :zone', ['zone' => $testResult['zone']]) }}<br />
                    {{ implode(' / ', $testResult['rates']) }}
                </flux:callout.text>
            </flux:callout>
        @endif
    </x-admin.card>

    {{-- Zone modal --}}
    <flux:modal name="zone-form" class="md:max-w-md">
        <form wire:submit="saveZone" class="space-y-4">
            <flux:heading size="lg">
                {{ $editingZoneId !== null ? __('Edit shipping zone') : __('Add shipping zone') }}
            </flux:heading>

            <flux:field>
                <flux:label>{{ __('Zone name') }}</flux:label>
                <flux:input wire:model="zoneName" :placeholder="__('Domestic, Europe, International...')" data-test="zone-name-input" />
                <flux:error name="zoneName" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Countries') }}</flux:label>
                <div class="max-h-56 space-y-1.5 overflow-y-auto rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                    @foreach (\App\Livewire\Admin\Settings\Shipping::COUNTRIES as $code => $name)
                        <flux:checkbox
                            wire:key="zone-country-{{ $code }}"
                            wire:model="zoneCountries"
                            value="{{ $code }}"
                            :label="$name"
                        />
                    @endforeach
                </div>
                <flux:error name="zoneCountries" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Regions') }}</flux:label>
                <flux:input wire:model="zoneRegions" placeholder="CA, NY, TX" data-test="zone-regions-input" />
                <flux:description>{{ __('Optional comma-separated region codes for finer matching.') }}</flux:description>
            </flux:field>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" data-test="save-zone-button">{{ __('Save zone') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Rate modal --}}
    <flux:modal name="rate-form" class="md:max-w-lg">
        <form wire:submit="saveRate" class="space-y-4">
            <flux:heading size="lg">
                {{ $editingRateId !== null ? __('Edit shipping rate') : __('Add shipping rate') }}
            </flux:heading>

            <flux:field>
                <flux:label>{{ __('Rate name') }}</flux:label>
                <flux:input wire:model="rateName" :placeholder="__('Standard, Express...')" data-test="rate-name-input" />
                <flux:error name="rateName" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Rate type') }}</flux:label>
                <flux:select wire:model.live="rateType" data-test="rate-type-select">
                    <flux:select.option value="flat">{{ __('Flat rate') }}</flux:select.option>
                    <flux:select.option value="weight">{{ __('Weight-based') }}</flux:select.option>
                    <flux:select.option value="price">{{ __('Price-based') }}</flux:select.option>
                </flux:select>
                <flux:error name="rateType" />
            </flux:field>

            @if ($rateType === 'flat')
                <flux:field class="max-w-48">
                    <flux:label>{{ __('Price') }}</flux:label>
                    <flux:input wire:model="rateFlatAmount" type="number" step="0.01" min="0" placeholder="5.00" data-test="rate-flat-amount-input" />
                    <flux:error name="rateFlatAmount" />
                </flux:field>
            @else
                <div class="space-y-2">
                    <flux:label>
                        {{ $rateType === 'weight' ? __('Weight ranges (grams)') : __('Order amount ranges') }}
                    </flux:label>

                    @foreach ($rateRanges as $index => $range)
                        <div wire:key="rate-range-{{ $index }}" class="flex items-end gap-2">
                            <flux:field class="flex-1">
                                <flux:label class="text-xs">{{ __('Min') }}</flux:label>
                                <flux:input wire:model="rateRanges.{{ $index }}.min" type="number" step="{{ $rateType === 'weight' ? '1' : '0.01' }}" min="0" size="sm" />
                            </flux:field>
                            <flux:field class="flex-1">
                                <flux:label class="text-xs">{{ __('Max') }}</flux:label>
                                <flux:input wire:model="rateRanges.{{ $index }}.max" type="number" step="{{ $rateType === 'weight' ? '1' : '0.01' }}" min="0" size="sm" />
                            </flux:field>
                            <flux:field class="flex-1">
                                <flux:label class="text-xs">{{ __('Price') }}</flux:label>
                                <flux:input wire:model="rateRanges.{{ $index }}.amount" type="number" step="0.01" min="0" size="sm" data-test="rate-range-amount-{{ $index }}" />
                            </flux:field>
                            <flux:button
                                variant="ghost"
                                size="sm"
                                icon="trash"
                                wire:click="removeRateRange({{ $index }})"
                                aria-label="{{ __('Remove range') }}"
                            />
                        </div>
                        <flux:error name="rateRanges.{{ $index }}.amount" />
                    @endforeach

                    <flux:button variant="ghost" size="sm" icon="plus" wire:click="addRateRange">
                        {{ __('Add range') }}
                    </flux:button>
                    <flux:error name="rateRanges" />
                </div>
            @endif

            <flux:field variant="inline">
                <flux:switch wire:model="rateActive" data-test="rate-active-switch" />
                <flux:label>{{ __('Active') }}</flux:label>
            </flux:field>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" data-test="save-rate-button">{{ __('Save rate') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
