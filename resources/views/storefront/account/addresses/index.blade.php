@extends('storefront.layout')

@section('title', 'Address Book')

@section('content')
<h1 class="text-2xl font-semibold text-slate-900">Address Book</h1>

@if ($errors->any())
    <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        <ul class="list-disc pl-4">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<section class="mt-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
    <h2 class="text-lg font-semibold text-slate-900">Add Address</h2>

    <form method="POST" action="{{ route('account.addresses.store') }}" class="mt-4 grid gap-3 sm:grid-cols-2">
        @csrf
        <label class="text-sm text-slate-700">
            Label
            <input name="label" value="{{ old('label') }}" class="mt-1 w-full rounded-md border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
        </label>
        <label class="text-sm text-slate-700">
            First name
            <input name="first_name" value="{{ old('first_name') }}" required class="mt-1 w-full rounded-md border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
        </label>
        <label class="text-sm text-slate-700">
            Last name
            <input name="last_name" value="{{ old('last_name') }}" required class="mt-1 w-full rounded-md border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
        </label>
        <label class="text-sm text-slate-700 sm:col-span-2">
            Address line 1
            <input name="address1" value="{{ old('address1') }}" required class="mt-1 w-full rounded-md border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
        </label>
        <label class="text-sm text-slate-700 sm:col-span-2">
            Address line 2
            <input name="address2" value="{{ old('address2') }}" class="mt-1 w-full rounded-md border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
        </label>
        <label class="text-sm text-slate-700">
            City
            <input name="city" value="{{ old('city') }}" required class="mt-1 w-full rounded-md border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
        </label>
        <label class="text-sm text-slate-700">
            Province/State
            <input name="province" value="{{ old('province') }}" class="mt-1 w-full rounded-md border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
        </label>
        <label class="text-sm text-slate-700">
            Province code
            <input name="province_code" value="{{ old('province_code') }}" class="mt-1 w-full rounded-md border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
        </label>
        <label class="text-sm text-slate-700">
            Postal code
            <input name="postal_code" value="{{ old('postal_code') }}" required class="mt-1 w-full rounded-md border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
        </label>
        <label class="text-sm text-slate-700">
            Country
            <input name="country" value="{{ old('country') }}" class="mt-1 w-full rounded-md border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
        </label>
        <label class="text-sm text-slate-700">
            Country code
            <input name="country_code" value="{{ old('country_code') }}" required maxlength="2" class="mt-1 w-full rounded-md border-slate-300 text-sm uppercase focus:border-slate-500 focus:ring-slate-500">
        </label>
        <label class="text-sm text-slate-700">
            Phone
            <input name="phone" value="{{ old('phone') }}" class="mt-1 w-full rounded-md border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
        </label>
        <label class="flex items-center gap-2 text-sm text-slate-700 sm:col-span-2">
            <input type="checkbox" name="is_default" value="1" class="rounded border-slate-300 text-slate-900 focus:ring-slate-500">
            Make default address
        </label>
        <div class="sm:col-span-2">
            <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
                Save Address
            </button>
        </div>
    </form>
</section>

<div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    @forelse ($addresses as $address)
        @php($json = is_array($address->address_json) ? $address->address_json : [])
        <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-semibold text-slate-900">
                {{ $address->label ?: 'Address' }}
                @if($address->is_default)
                    <span class="text-xs font-medium text-slate-500">(Default)</span>
                @endif
            </h2>

            <div class="mt-3 text-sm text-slate-700">
                <p>{{ trim(($json['first_name'] ?? '').' '.($json['last_name'] ?? '')) }}</p>
                <p>{{ $json['address1'] ?? '' }}</p>
                @if (! empty($json['address2']))
                    <p>{{ $json['address2'] }}</p>
                @endif
                <p>{{ $json['city'] ?? '' }} {{ $json['postal_code'] ?? '' }}</p>
                <p>{{ $json['country_code'] ?? ($json['country'] ?? '') }}</p>
            </div>

            <details class="mt-4 rounded-lg border border-slate-200 p-3">
                <summary class="cursor-pointer text-sm font-medium text-slate-700">Edit</summary>
                <form method="POST" action="{{ route('account.addresses.update', $address->id) }}" class="mt-3 grid gap-2 text-sm">
                    @csrf
                    @method('PUT')
                    <input name="label" value="{{ $address->label }}" placeholder="Label" class="rounded-md border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                    <input name="first_name" value="{{ $json['first_name'] ?? '' }}" required placeholder="First name" class="rounded-md border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                    <input name="last_name" value="{{ $json['last_name'] ?? '' }}" required placeholder="Last name" class="rounded-md border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                    <input name="address1" value="{{ $json['address1'] ?? '' }}" required placeholder="Address line 1" class="rounded-md border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                    <input name="address2" value="{{ $json['address2'] ?? '' }}" placeholder="Address line 2" class="rounded-md border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                    <input name="city" value="{{ $json['city'] ?? '' }}" required placeholder="City" class="rounded-md border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                    <input name="province" value="{{ $json['province'] ?? '' }}" placeholder="Province/State" class="rounded-md border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                    <input name="province_code" value="{{ $json['province_code'] ?? '' }}" placeholder="Province code" class="rounded-md border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                    <input name="postal_code" value="{{ $json['postal_code'] ?? '' }}" required placeholder="Postal code" class="rounded-md border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                    <input name="country" value="{{ $json['country'] ?? '' }}" placeholder="Country" class="rounded-md border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                    <input name="country_code" value="{{ $json['country_code'] ?? '' }}" required maxlength="2" placeholder="Country code" class="rounded-md border-slate-300 text-sm uppercase focus:border-slate-500 focus:ring-slate-500">
                    <input name="phone" value="{{ $json['phone'] ?? '' }}" placeholder="Phone" class="rounded-md border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="is_default" value="1" @checked((bool) $address->is_default) class="rounded border-slate-300 text-slate-900 focus:ring-slate-500">
                        Default address
                    </label>
                    <button type="submit" class="rounded-md bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-700">
                        Update Address
                    </button>
                </form>
            </details>

            <form method="POST" action="{{ route('account.addresses.destroy', $address->id) }}" class="mt-3">
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-100">
                    Delete Address
                </button>
            </form>
        </article>
    @empty
        <p class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-600">No addresses saved yet.</p>
    @endforelse
</div>
@endsection
