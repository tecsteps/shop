@extends('admin.layout')

@section('title', 'Settings · General')

@section('content')
    <div class="mb-4">
        <h1 class="text-2xl font-semibold text-zinc-900">Settings</h1>
        <p class="mt-1 text-sm text-zinc-600">General store configuration.</p>
    </div>

    @include('admin.settings._tabs')

    <form method="POST" action="{{ route('admin.settings.general.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        <section class="rounded-lg border border-zinc-200 bg-white p-5">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-700">Store Details</h2>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <label class="block text-sm">
                    <span class="mb-1 block text-zinc-700">Store name</span>
                    <input type="text" name="name" value="{{ old('name', $store->name) }}" required class="w-full rounded-md border border-zinc-300 px-3 py-2">
                </label>

                <label class="block text-sm">
                    <span class="mb-1 block text-zinc-700">Store handle</span>
                    <input type="text" value="{{ $store->handle }}" disabled class="w-full rounded-md border border-zinc-200 bg-zinc-100 px-3 py-2 text-zinc-600">
                </label>

                <label class="block text-sm">
                    <span class="mb-1 block text-zinc-700">Default currency</span>
                    <input type="text" name="default_currency" value="{{ old('default_currency', $store->default_currency) }}" maxlength="3" class="w-full rounded-md border border-zinc-300 px-3 py-2">
                </label>

                <label class="block text-sm">
                    <span class="mb-1 block text-zinc-700">Default locale</span>
                    <input type="text" name="default_locale" value="{{ old('default_locale', $store->default_locale) }}" class="w-full rounded-md border border-zinc-300 px-3 py-2">
                </label>

                <label class="block text-sm sm:col-span-2">
                    <span class="mb-1 block text-zinc-700">Timezone</span>
                    <input type="text" name="timezone" value="{{ old('timezone', $store->timezone) }}" class="w-full rounded-md border border-zinc-300 px-3 py-2">
                </label>
            </div>
        </section>

        <section class="rounded-lg border border-zinc-200 bg-white p-5">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-700">Operational Defaults</h2>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <label class="block text-sm sm:col-span-2">
                    <span class="mb-1 block text-zinc-700">Contact email</span>
                    <input type="email" name="contact_email" value="{{ old('contact_email', $settingsJson['contact_email'] ?? '') }}" class="w-full rounded-md border border-zinc-300 px-3 py-2">
                </label>

                <label class="block text-sm">
                    <span class="mb-1 block text-zinc-700">Order number prefix</span>
                    <input type="text" name="order_number_prefix" value="{{ old('order_number_prefix', $settingsJson['order_number_prefix'] ?? '#') }}" class="w-full rounded-md border border-zinc-300 px-3 py-2">
                </label>

                <label class="block text-sm">
                    <span class="mb-1 block text-zinc-700">Order number start</span>
                    <input type="number" min="1" name="order_number_start" value="{{ old('order_number_start', $settingsJson['order_number_start'] ?? 1001) }}" class="w-full rounded-md border border-zinc-300 px-3 py-2">
                </label>
            </div>
        </section>

        <button type="submit" class="rounded-md bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700">
            Save General Settings
        </button>
    </form>
@endsection
