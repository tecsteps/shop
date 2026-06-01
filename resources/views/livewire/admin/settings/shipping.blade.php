@php
    use App\Support\Storefront\PriceFormatter;

    $currency = $currentStore->default_currency ?? 'USD';
@endphp

<div>
    <x-admin.breadcrumbs :items="[['label' => __('Settings'), 'href' => route('admin.settings.index')], ['label' => __('Shipping')]]" />

    <flux:heading size="xl" level="1" class="mb-6">{{ __('Settings') }}</flux:heading>

    <x-admin.settings-tabs active="shipping" />

    <div class="mb-4 flex justify-end">
        <flux:button variant="primary" icon="plus" wire:click="openZoneModal" data-test="add-zone">{{ __('Add zone') }}</flux:button>
    </div>

    <div class="space-y-4">
        @forelse ($this->zones as $zone)
            <x-admin.card wire:key="zone-{{ $zone->id }}">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <flux:heading size="md">{{ $zone->name }}</flux:heading>
                        <flux:text class="text-sm">{{ __('Countries') }}: {{ implode(', ', $zone->countries_json ?? []) ?: '—' }}</flux:text>
                    </div>
                    <div class="flex gap-2">
                        <flux:button size="sm" variant="ghost" wire:click="openZoneModal({{ $zone->id }})">{{ __('Edit') }}</flux:button>
                        <flux:button size="sm" variant="ghost" icon="trash" wire:click="deleteZone({{ $zone->id }})" wire:confirm="{{ __('Delete this zone?') }}" :aria-label="__('Delete zone')" />
                    </div>
                </div>

                <flux:separator class="my-4" />

                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase text-zinc-500">
                            <th class="pb-2">{{ __('Name') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Config') }}</th>
                            <th>{{ __('Active') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($zone->rates as $rate)
                            <tr class="border-t border-zinc-100 dark:border-zinc-800" wire:key="rate-{{ $rate->id }}">
                                <td class="py-2">{{ $rate->name }}</td>
                                <td><flux:badge size="sm" color="zinc">{{ $rate->type->value }}</flux:badge></td>
                                <td>{{ isset($rate->config_json['price']) ? PriceFormatter::format((int) $rate->config_json['price'], $currency) : '—' }}</td>
                                <td>@if ($rate->is_active)<flux:badge size="sm" color="green">{{ __('On') }}</flux:badge>@else<flux:badge size="sm" color="zinc">{{ __('Off') }}</flux:badge>@endif</td>
                                <td class="text-right">
                                    <flux:button size="sm" variant="ghost" wire:click="openRateModal({{ $zone->id }}, {{ $rate->id }})">{{ __('Edit') }}</flux:button>
                                    <flux:button size="sm" variant="ghost" icon="trash" wire:click="deleteRate({{ $rate->id }})" :aria-label="__('Delete rate')" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="mt-3">
                    <flux:button size="sm" variant="ghost" icon="plus" wire:click="openRateModal({{ $zone->id }})">{{ __('Add rate') }}</flux:button>
                </div>
            </x-admin.card>
        @empty
            <x-admin.card class="text-center">
                <flux:text>{{ __('No shipping zones yet. Add one to start charging for shipping.') }}</flux:text>
            </x-admin.card>
        @endforelse
    </div>

    {{-- Test address tool. --}}
    <x-admin.card class="mt-6" title="{{ __('Test shipping address') }}">
        <flux:text class="mb-4 text-sm">{{ __('Enter an address to see which shipping zone and rates match.') }}</flux:text>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <flux:field><flux:label>{{ __('Country') }}</flux:label><flux:input wire:model="testAddress.country" placeholder="DE" /></flux:field>
            <flux:field><flux:label>{{ __('State/Region') }}</flux:label><flux:input wire:model="testAddress.province_code" /></flux:field>
            <flux:field><flux:label>{{ __('City') }}</flux:label><flux:input wire:model="testAddress.city" /></flux:field>
            <flux:field><flux:label>{{ __('ZIP/Postal code') }}</flux:label><flux:input wire:model="testAddress.postal_code" /></flux:field>
        </div>
        <div class="mt-4">
            <flux:button wire:click="testShippingAddress" data-test="test-shipping">{{ __('Test') }}</flux:button>
        </div>

        @if ($testResult !== null)
            <div class="mt-4">
                @if ($testResult['matched'])
                    <flux:text class="font-medium">{{ __('Matched zone:') }} {{ $testResult['zone'] }}</flux:text>
                    <ul class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                        @foreach ($testResult['rates'] as $rate)
                            <li>{{ $rate['name'] }} — {{ PriceFormatter::format((int) $rate['price'], $currency) }}</li>
                        @endforeach
                    </ul>
                @else
                    <flux:callout variant="warning" icon="exclamation-triangle">
                        <flux:callout.text>{{ __('No shipping zone matches this address.') }}</flux:callout.text>
                    </flux:callout>
                @endif
            </div>
        @endif
    </x-admin.card>

    {{-- Zone modal. --}}
    <flux:modal wire:model.self="showZoneModal" name="zone-form" class="md:w-96">
        <div class="space-y-4">
            <flux:heading size="lg">{{ $editingZoneId ? __('Edit shipping zone') : __('Add shipping zone') }}</flux:heading>
            <flux:field>
                <flux:label>{{ __('Zone name') }}</flux:label>
                <flux:input wire:model="zoneName" placeholder="Domestic, Europe, International..." data-test="zone-name" />
                <flux:error name="zoneName" />
            </flux:field>
            <flux:field>
                <flux:label>{{ __('Countries') }}</flux:label>
                <flux:input wire:model="zoneCountriesInput" placeholder="US, CA, GB" />
                <flux:description>{{ __('Comma-separated ISO country codes.') }}</flux:description>
            </flux:field>
            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="$set('showZoneModal', false)">{{ __('Cancel') }}</flux:button>
                <flux:button variant="primary" wire:click="saveZone" data-test="save-zone">{{ __('Save zone') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Rate modal. --}}
    <flux:modal wire:model.self="showRateModal" name="rate-form" class="md:w-96">
        <div class="space-y-4">
            <flux:heading size="lg">{{ $editingRateId ? __('Edit shipping rate') : __('Add shipping rate') }}</flux:heading>
            <flux:field>
                <flux:label>{{ __('Rate name') }}</flux:label>
                <flux:input wire:model="rateName" placeholder="Standard, Express..." data-test="rate-name" />
                <flux:error name="rateName" />
            </flux:field>
            <flux:field>
                <flux:label>{{ __('Rate type') }}</flux:label>
                <flux:select wire:model.live="rateType">
                    <flux:select.option value="flat">{{ __('Flat rate') }}</flux:select.option>
                    <flux:select.option value="weight">{{ __('Weight-based') }}</flux:select.option>
                    <flux:select.option value="price">{{ __('Price-based') }}</flux:select.option>
                    <flux:select.option value="carrier">{{ __('Carrier-calculated') }}</flux:select.option>
                </flux:select>
            </flux:field>

            @if ($rateType === 'carrier')
                <flux:callout icon="information-circle">
                    <flux:callout.text>{{ __('Carrier-calculated rates require a carrier integration to be configured.') }}</flux:callout.text>
                </flux:callout>
            @else
                @if ($rateType === 'weight' || $rateType === 'price')
                    <div class="grid grid-cols-2 gap-3">
                        <flux:field><flux:label>{{ $rateType === 'weight' ? __('Min weight (g)') : __('Min amount') }}</flux:label><flux:input type="number" wire:model="rateConfig.min" /></flux:field>
                        <flux:field><flux:label>{{ $rateType === 'weight' ? __('Max weight (g)') : __('Max amount') }}</flux:label><flux:input type="number" wire:model="rateConfig.max" /></flux:field>
                    </div>
                @endif
                <flux:field>
                    <flux:label>{{ __('Price') }}</flux:label>
                    <flux:input type="number" step="0.01" wire:model="rateConfig.price" data-test="rate-price" />
                </flux:field>
            @endif

            <flux:switch wire:model="rateActive" :label="__('Active')" />

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="$set('showRateModal', false)">{{ __('Cancel') }}</flux:button>
                <flux:button variant="primary" wire:click="saveRate" data-test="save-rate">{{ __('Save rate') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
