<div>
    @include('livewire.admin.components.page-heading', ['title' => 'Shipping', 'description' => 'Manage delivery zones and the rates customers see at checkout.', 'breadcrumbs' => [['label' => 'Settings', 'href' => '/admin/settings']]])
    @include('livewire.admin.components.settings-nav', ['active' => 'Shipping'])

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1.2fr)_minmax(20rem,.8fr)]">
        <div class="grid content-start gap-5">
            <section class="grid gap-4" aria-label="Shipping zones">
                @forelse ($zones as $zone)
                    <article wire:key="shipping-zone-{{ $zone->id }}" class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-zinc-200 px-5 py-4 dark:border-zinc-800">
                            <div>
                                <h2 class="font-semibold">{{ $zone->name }}</h2>
                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Countries: {{ implode(', ', $zone->countries ?? []) }}</p>
                                @if (count($zone->regions ?? []))<p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Regions: {{ implode(', ', $zone->regions) }}</p>@endif
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" wire:click="editZone({{ $zone->id }})" class="inline-flex min-h-9 items-center rounded-lg border border-zinc-300 px-3 text-sm font-medium hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">Edit</button>
                                <button type="button" wire:click="deleteZone({{ $zone->id }})" wire:confirm="Delete {{ $zone->name }} and its shipping rates?" class="inline-flex min-h-9 items-center rounded-lg px-3 text-sm font-medium text-rose-700 hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-950/40">Delete</button>
                            </div>
                        </div>

                        @if ($zone->rates->isNotEmpty())
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                                    <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:bg-zinc-950"><tr><th class="px-5 py-3">Name</th><th class="px-5 py-3">Type</th><th class="px-5 py-3">Config</th><th class="px-5 py-3">Active</th><th class="px-5 py-3 text-right">Actions</th></tr></thead>
                                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                        @foreach ($zone->rates as $rate)
                                            <tr wire:key="shipping-rate-{{ $rate->id }}">
                                                <td class="px-5 py-3 font-medium">{{ $rate->name }}</td>
                                                <td class="px-5 py-3"><span class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium capitalize dark:bg-zinc-800">{{ $rate->type }}</span></td>
                                                <td class="px-5 py-3 text-zinc-600 dark:text-zinc-300">
                                                    @if ($rate->type === 'flat'){{ number_format($rate->price_amount / 100, 2) }} {{ data_get(app()->bound('current_store') ? app('current_store') : null, 'default_currency', 'EUR') }}
                                                    @elseif ($rate->type === 'carrier'){{ strtoupper($rate->config_json['carrier'] ?? 'carrier') }} · {{ $rate->config_json['service'] ?? 'service' }}
                                                    @else{{ count($rate->config_json['tiers'] ?? $rate->config_json['ranges'] ?? []) }} price tiers @endif
                                                </td>
                                                <td class="px-5 py-3"><button type="button" wire:click="toggleRate({{ $rate->id }})" aria-label="Toggle {{ $rate->name }} rate" class="inline-flex min-h-9 items-center gap-2 rounded-lg px-2 text-sm"><span class="size-2.5 rounded-full {{ $rate->is_active ? 'bg-emerald-500' : 'bg-zinc-400' }}"></span>{{ $rate->is_active ? 'Active' : 'Inactive' }}</button></td>
                                                <td class="px-5 py-3 text-right"><div class="inline-flex items-center gap-2"><button type="button" wire:click="editRate({{ $rate->id }})" class="rounded-lg px-2.5 py-1.5 font-medium hover:bg-zinc-100 dark:hover:bg-zinc-800">Edit</button><button type="button" wire:click="deleteRate({{ $rate->id }})" wire:confirm="Delete the {{ $rate->name }} rate?" class="rounded-lg px-2.5 py-1.5 font-medium text-rose-700 hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-950/40">Delete</button></div></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="px-5 py-4 text-sm text-zinc-500">No rates in this zone yet.</p>
                        @endif

                        <div class="border-t border-zinc-100 px-5 py-3 dark:border-zinc-800"><button type="button" wire:click="prepareRate({{ $zone->id }})" class="text-sm font-semibold text-blue-700 hover:underline dark:text-blue-300">+ Add rate</button></div>
                    </article>
                @empty
                    <div class="rounded-2xl border border-dashed border-zinc-300 bg-white px-6 py-12 text-center dark:border-zinc-700 dark:bg-zinc-900"><h2 class="font-semibold">No shipping zones</h2><p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Add a zone to define where your store can deliver.</p></div>
                @endforelse
            </section>

            <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-6 dark:border-zinc-800 dark:bg-zinc-900" aria-labelledby="shipping-test-heading">
                <h2 id="shipping-test-heading" class="font-semibold">Test shipping address</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Enter an address and basket size to see which zone and rates match.</p>
                <form wire:submit="testShippingAddress" class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div><label for="test-country" class="mb-1.5 block text-sm font-medium">Country code</label><input id="test-country" type="text" maxlength="2" wire:model="testCountryCode" class="min-h-10 w-full rounded-lg border border-zinc-300 px-3 uppercase dark:border-zinc-700 dark:bg-zinc-950">@error('testCountryCode')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror</div>
                    <div><label for="test-region" class="mb-1.5 block text-sm font-medium">State / region</label><input id="test-region" type="text" wire:model="testRegionCode" placeholder="CA" class="min-h-10 w-full rounded-lg border border-zinc-300 px-3 dark:border-zinc-700 dark:bg-zinc-950"></div>
                    <div><label for="test-city" class="mb-1.5 block text-sm font-medium">City</label><input id="test-city" type="text" wire:model="testCity" class="min-h-10 w-full rounded-lg border border-zinc-300 px-3 dark:border-zinc-700 dark:bg-zinc-950"></div>
                    <div><label for="test-postal" class="mb-1.5 block text-sm font-medium">ZIP / postal code</label><input id="test-postal" type="text" wire:model="testPostalCode" class="min-h-10 w-full rounded-lg border border-zinc-300 px-3 dark:border-zinc-700 dark:bg-zinc-950"></div>
                    <div><label for="test-subtotal" class="mb-1.5 block text-sm font-medium">Order subtotal (minor units)</label><input id="test-subtotal" type="number" min="0" wire:model="testSubtotalAmount" class="min-h-10 w-full rounded-lg border border-zinc-300 px-3 dark:border-zinc-700 dark:bg-zinc-950"></div>
                    <div><label for="test-weight" class="mb-1.5 block text-sm font-medium">Shipment weight (g)</label><input id="test-weight" type="number" min="0" wire:model="testWeightGrams" class="min-h-10 w-full rounded-lg border border-zinc-300 px-3 dark:border-zinc-700 dark:bg-zinc-950"></div>
                    <div class="sm:col-span-2"><button type="submit" class="inline-flex min-h-10 items-center rounded-xl bg-zinc-900 px-4 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900">Test address</button></div>
                </form>
                @if ($hasTestedAddress)
                    @if ($testResult === null)
                        <p class="mt-4 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-100">No shipping zone matches this address.</p>
                    @else
                        <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm dark:border-emerald-900 dark:bg-emerald-950/40"><p class="font-semibold">Matched zone: {{ $testResult['zone'] }}</p>@forelse ($testResult['rates'] as $rate)<p class="mt-1">{{ $rate['name'] }} · {{ number_format($rate['price_amount'] / 100, 2) }} {{ data_get(app()->bound('current_store') ? app('current_store') : null, 'default_currency', 'EUR') }}</p>@empty<p class="mt-1 text-zinc-600 dark:text-zinc-300">No active rates match this basket.</p>@endforelse</div>
                    @endif
                @endif
            </section>
        </div>

        <div class="grid content-start gap-5">
            <form wire:submit="saveZone" class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="font-semibold">{{ $editingZoneId ? 'Edit shipping zone' : 'Add shipping zone' }}</h2>
                <div class="mt-4 grid gap-4">
                    <div><label for="zone-name" class="mb-1.5 block text-sm font-medium">Zone name</label><input id="zone-name" type="text" wire:model="zoneName" placeholder="Domestic, Europe, International" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-950">@error('zoneName')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror</div>
                    <div><label for="zone-countries" class="mb-1.5 block text-sm font-medium">Countries</label><input id="zone-countries" type="text" wire:model="countries" placeholder="DE, AT, CH" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm uppercase dark:border-zinc-700 dark:bg-zinc-950"><p class="mt-1 text-xs text-zinc-500">Two-letter country codes separated by commas.</p>@error('countries')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror</div>
                    <div><label for="zone-regions" class="mb-1.5 block text-sm font-medium">Regions (optional)</label><input id="zone-regions" type="text" wire:model="regions" placeholder="US-CA, US-NY" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm uppercase dark:border-zinc-700 dark:bg-zinc-950"><p class="mt-1 text-xs text-zinc-500">Region codes must use CC-XX format.</p>@error('regions')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror</div>
                    <div class="flex gap-2"><button type="submit" class="inline-flex min-h-10 flex-1 items-center justify-center rounded-xl bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700">Save zone</button>@if ($editingZoneId)<button type="button" wire:click="cancelZoneEdit" class="inline-flex min-h-10 items-center rounded-xl border border-zinc-300 px-3 text-sm font-medium dark:border-zinc-700">Cancel</button>@endif</div>
                </div>
            </form>

            <form wire:submit="saveRate" class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="font-semibold">{{ $editingRateId ? 'Edit shipping rate' : 'Add shipping rate' }}</h2>
                <div class="mt-4 grid gap-4">
                    <div><label for="rate-zone" class="mb-1.5 block text-sm font-medium">Shipping zone</label><select id="rate-zone" wire:model="zoneId" class="min-h-11 w-full rounded-xl border border-zinc-300 bg-white px-3 text-sm dark:border-zinc-700 dark:bg-zinc-950"><option value="">Select a zone</option>@foreach ($zones as $zone)<option value="{{ $zone->id }}">{{ $zone->name }}</option>@endforeach</select>@error('zoneId')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror</div>
                    <div><label for="rate-name" class="mb-1.5 block text-sm font-medium">Rate name</label><input id="rate-name" type="text" wire:model="rateName" placeholder="Standard, Express" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-950">@error('rateName')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror</div>
                    <div><label for="rate-type" class="mb-1.5 block text-sm font-medium">Rate type</label><select id="rate-type" wire:model.live="rateType" class="min-h-11 w-full rounded-xl border border-zinc-300 bg-white px-3 text-sm dark:border-zinc-700 dark:bg-zinc-950"><option value="flat">Flat rate</option><option value="weight">Weight-based</option><option value="price">Price-based</option><option value="carrier">Carrier-calculated</option></select></div>

                    @if ($rateType === 'flat' || $rateType === 'carrier')
                        <div><label for="rate-price" class="mb-1.5 block text-sm font-medium">{{ $rateType === 'carrier' ? 'Carrier fallback price (minor units)' : 'Price (minor units)' }}</label><input id="rate-price" type="number" min="0" step="1" wire:model="rateAmount" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-950">@error('rateAmount')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror</div>
                    @endif

                    @if ($rateType === 'weight')
                        <fieldset class="grid gap-3"><legend class="text-sm font-medium">Weight tiers (grams)</legend>@foreach ($weightTiers as $index => $tier)<div wire:key="weight-tier-{{ $index }}" class="grid grid-cols-[1fr_1fr_1fr_auto] gap-2"><label class="sr-only" for="weight-min-{{ $index }}">Minimum weight</label><input id="weight-min-{{ $index }}" type="number" min="0" wire:model="weightTiers.{{ $index }}.min_weight_g" placeholder="Min g" class="min-w-0 rounded-lg border border-zinc-300 px-2 py-2 text-xs dark:border-zinc-700 dark:bg-zinc-950"><label class="sr-only" for="weight-max-{{ $index }}">Maximum weight</label><input id="weight-max-{{ $index }}" type="number" min="0" wire:model="weightTiers.{{ $index }}.max_weight_g" placeholder="Max g" class="min-w-0 rounded-lg border border-zinc-300 px-2 py-2 text-xs dark:border-zinc-700 dark:bg-zinc-950"><label class="sr-only" for="weight-price-{{ $index }}">Tier price</label><input id="weight-price-{{ $index }}" type="number" min="0" wire:model="weightTiers.{{ $index }}.price_amount" placeholder="Price" class="min-w-0 rounded-lg border border-zinc-300 px-2 py-2 text-xs dark:border-zinc-700 dark:bg-zinc-950"><button type="button" wire:click="removeWeightTier({{ $index }})" aria-label="Remove weight tier" class="px-2 text-rose-700">×</button></div>@endforeach<button type="button" wire:click="addWeightTier" class="justify-self-start text-sm font-semibold text-blue-700 dark:text-blue-300">+ Add weight tier</button></fieldset>
                    @endif

                    @if ($rateType === 'price')
                        <fieldset class="grid gap-3"><legend class="text-sm font-medium">Order value tiers (minor units)</legend>@foreach ($priceTiers as $index => $tier)<div wire:key="price-tier-{{ $index }}" class="grid grid-cols-[1fr_1fr_1fr_auto] gap-2"><label class="sr-only" for="price-min-{{ $index }}">Minimum order amount</label><input id="price-min-{{ $index }}" type="number" min="0" wire:model="priceTiers.{{ $index }}.min_order_amount" placeholder="Min" class="min-w-0 rounded-lg border border-zinc-300 px-2 py-2 text-xs dark:border-zinc-700 dark:bg-zinc-950"><label class="sr-only" for="price-max-{{ $index }}">Maximum order amount</label><input id="price-max-{{ $index }}" type="number" min="0" wire:model="priceTiers.{{ $index }}.max_order_amount" placeholder="Max" class="min-w-0 rounded-lg border border-zinc-300 px-2 py-2 text-xs dark:border-zinc-700 dark:bg-zinc-950"><label class="sr-only" for="price-amount-{{ $index }}">Tier shipping amount</label><input id="price-amount-{{ $index }}" type="number" min="0" wire:model="priceTiers.{{ $index }}.price_amount" placeholder="Rate" class="min-w-0 rounded-lg border border-zinc-300 px-2 py-2 text-xs dark:border-zinc-700 dark:bg-zinc-950"><button type="button" wire:click="removePriceTier({{ $index }})" aria-label="Remove price tier" class="px-2 text-rose-700">×</button></div>@endforeach<button type="button" wire:click="addPriceTier" class="justify-self-start text-sm font-semibold text-blue-700 dark:text-blue-300">+ Add price tier</button></fieldset>
                    @endif

                    @if ($rateType === 'carrier')
                        <div class="rounded-xl border border-blue-200 bg-blue-50 p-3 text-sm text-blue-900 dark:border-blue-900 dark:bg-blue-950/40 dark:text-blue-100">Carrier-calculated rates use the fallback price until a carrier integration is configured.</div>
                        <div><label for="carrier-name" class="mb-1.5 block text-sm font-medium">Carrier</label><select id="carrier-name" wire:model="carrier" class="min-h-10 w-full rounded-lg border border-zinc-300 px-3 dark:border-zinc-700 dark:bg-zinc-950"><option value="ups">UPS</option><option value="fedex">FedEx</option><option value="dhl">DHL</option></select></div>
                        <div><label for="carrier-service" class="mb-1.5 block text-sm font-medium">Service</label><input id="carrier-service" type="text" wire:model="carrierService" class="min-h-10 w-full rounded-lg border border-zinc-300 px-3 dark:border-zinc-700 dark:bg-zinc-950"></div>
                    @endif

                    <div class="grid grid-cols-2 gap-3"><div><label for="rate-min" class="mb-1.5 block text-sm font-medium">Minimum order (minor units)</label><input id="rate-min" type="number" min="0" wire:model="minOrderAmount" class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">@error('minOrderAmount')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div><div><label for="rate-max" class="mb-1.5 block text-sm font-medium">Maximum order</label><input id="rate-max" type="number" min="0" wire:model="maxOrderAmount" class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950">@error('maxOrderAmount')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div></div>
                    <label class="flex min-h-10 items-center gap-2 text-sm"><input type="checkbox" wire:model="rateActive" class="size-4 rounded border-zinc-300 text-blue-600"><span>Active</span></label>
                    <div class="flex gap-2"><button type="submit" class="inline-flex min-h-10 flex-1 items-center justify-center rounded-xl bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700">Save rate</button>@if ($editingRateId)<button type="button" wire:click="cancelRateEdit" class="inline-flex min-h-10 items-center rounded-xl border border-zinc-300 px-3 text-sm font-medium dark:border-zinc-700">Cancel</button>@endif</div>
                </div>
            </form>
        </div>
    </div>
</div>
