@extends('admin.layout')

@section('title', 'Settings · Taxes')

@section('content')
    <div class="mb-4">
        <h1 class="text-2xl font-semibold text-zinc-900">Tax Settings</h1>
        <p class="mt-1 text-sm text-zinc-600">Control tax mode, provider, and defaults.</p>
    </div>

    @include('admin.settings._tabs')

    <form method="POST" action="{{ route('admin.settings.taxes.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        <section class="rounded-lg border border-zinc-200 bg-white p-5">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-700">Configuration</h2>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <label class="block text-sm">
                    <span class="mb-1 block text-zinc-700">Mode</span>
                    <select name="mode" class="w-full rounded-md border border-zinc-300 px-3 py-2">
                        @foreach (\App\Enums\TaxMode::cases() as $mode)
                            <option value="{{ $mode->value }}" @selected(old('mode', $taxSetting->mode->value) === $mode->value)>
                                {{ $mode->value }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="block text-sm">
                    <span class="mb-1 block text-zinc-700">Provider</span>
                    <input type="text" name="provider" value="{{ old('provider', $taxSetting->provider) }}" class="w-full rounded-md border border-zinc-300 px-3 py-2">
                </label>

                <label class="block text-sm">
                    <span class="mb-1 block text-zinc-700">Default rate (basis points)</span>
                    <input type="number" min="0" max="10000" name="default_rate_bps" value="{{ old('default_rate_bps', $configJson['default_rate_bps'] ?? 0) }}" class="w-full rounded-md border border-zinc-300 px-3 py-2">
                </label>

                <label class="block text-sm">
                    <span class="mb-1 block text-zinc-700">Provider API key</span>
                    <input type="text" name="provider_api_key" value="{{ old('provider_api_key', $configJson['provider_api_key'] ?? '') }}" class="w-full rounded-md border border-zinc-300 px-3 py-2">
                </label>

                <label class="inline-flex items-center gap-2 text-sm text-zinc-700 sm:col-span-2">
                    <input type="checkbox" name="prices_include_tax" value="1" @checked(old('prices_include_tax', $taxSetting->prices_include_tax)) class="rounded border-zinc-300 text-zinc-900">
                    Prices include tax
                </label>
            </div>
        </section>

        <button type="submit" class="rounded-md bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700">
            Save Tax Settings
        </button>
    </form>
@endsection
