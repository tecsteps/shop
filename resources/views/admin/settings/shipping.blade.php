@extends('admin.layout')

@section('title', 'Settings · Shipping')

@section('content')
    @php
        $shipping = (array) ($settingsJson['shipping'] ?? []);
    @endphp

    <div class="mb-4">
        <h1 class="text-2xl font-semibold text-zinc-900">Shipping Settings</h1>
        <p class="mt-1 text-sm text-zinc-600">Save baseline shipping defaults and inspect configured zones/rates.</p>
    </div>

    @include('admin.settings._tabs')

    <form method="POST" action="{{ route('admin.settings.shipping.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        <section class="rounded-lg border border-zinc-200 bg-white p-5">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-700">Defaults</h2>

            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                <label class="block text-sm">
                    <span class="mb-1 block text-zinc-700">Free shipping threshold (minor units)</span>
                    <input
                        type="number"
                        min="0"
                        name="free_shipping_threshold_amount"
                        value="{{ old('free_shipping_threshold_amount', $shipping['free_shipping_threshold_amount'] ?? '') }}"
                        class="w-full rounded-md border border-zinc-300 px-3 py-2"
                    >
                </label>

                <label class="block text-sm">
                    <span class="mb-1 block text-zinc-700">Default shipping rate (minor units)</span>
                    <input
                        type="number"
                        min="0"
                        name="default_shipping_rate_amount"
                        value="{{ old('default_shipping_rate_amount', $shipping['default_shipping_rate_amount'] ?? '') }}"
                        class="w-full rounded-md border border-zinc-300 px-3 py-2"
                    >
                </label>

                <label class="block text-sm">
                    <span class="mb-1 block text-zinc-700">Bank transfer cancel days</span>
                    <input
                        type="number"
                        min="1"
                        max="60"
                        name="bank_transfer_cancel_days"
                        value="{{ old('bank_transfer_cancel_days', $settingsJson['bank_transfer_cancel_days'] ?? 7) }}"
                        class="w-full rounded-md border border-zinc-300 px-3 py-2"
                    >
                </label>
            </div>
        </section>

        <button type="submit" class="rounded-md bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700">
            Save Shipping Settings
        </button>
    </form>

    <section class="mt-6 overflow-hidden rounded-lg border border-zinc-200 bg-white">
        <div class="border-b border-zinc-200 px-4 py-3">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-700">Configured Zones and Rates</h2>
        </div>

        <div class="divide-y divide-zinc-200">
            @forelse ($zones as $zone)
                <div class="p-4">
                    <h3 class="font-medium text-zinc-900">{{ $zone->name }}</h3>
                    <p class="mt-1 text-xs text-zinc-500">Countries: {{ implode(', ', $zone->countries_json ?? []) ?: 'none' }}</p>

                    <div class="mt-3 overflow-x-auto rounded border border-zinc-200">
                        <table class="min-w-full divide-y divide-zinc-200 text-sm">
                            <thead class="bg-zinc-50 text-left text-xs uppercase tracking-wide text-zinc-500">
                                <tr>
                                    <th class="px-3 py-2">Rate</th>
                                    <th class="px-3 py-2">Type</th>
                                    <th class="px-3 py-2">Config</th>
                                    <th class="px-3 py-2">Active</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 bg-white">
                                @forelse ($zone->rates as $rate)
                                    <tr>
                                        <td class="px-3 py-2">{{ $rate->name }}</td>
                                        <td class="px-3 py-2">{{ $rate->type->value }}</td>
                                        <td class="px-3 py-2"><code>{{ json_encode($rate->config_json) }}</code></td>
                                        <td class="px-3 py-2">{{ $rate->is_active ? 'yes' : 'no' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-3 py-4 text-center text-zinc-500">No rates in this zone.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <p class="p-6 text-sm text-zinc-500">No shipping zones configured.</p>
            @endforelse
        </div>
    </section>
@endsection
