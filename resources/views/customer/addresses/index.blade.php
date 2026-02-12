@extends('storefront.layout')

@section('content')
<section class="space-y-6">
    <header class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-zinc-900">Your Addresses</h1>
            <p class="mt-2 text-sm text-zinc-600">Manage default, shipping, and billing addresses.</p>
        </div>
        <a href="{{ route('storefront.account.dashboard') }}" class="rounded-md border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100">Back to account</a>
    </header>

    <section class="rounded-xl border border-zinc-200 bg-white p-5">
        <h2 class="text-xl font-semibold text-zinc-900">Add new address</h2>
        <form method="POST" action="{{ route('storefront.account.addresses.store') }}" class="mt-4 grid gap-3 sm:grid-cols-2">
            @csrf
            <div class="sm:col-span-2">
                <label for="new_label" class="block text-sm font-medium text-zinc-700">Label</label>
                <input id="new_label" type="text" name="label" value="{{ old('label') }}" placeholder="Home, Office, etc." class="mt-1 w-full rounded-md border-zinc-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
            </div>
            <div>
                <label for="new_first_name" class="block text-sm font-medium text-zinc-700">First name</label>
                <input id="new_first_name" type="text" name="first_name" value="{{ old('first_name') }}" required class="mt-1 w-full rounded-md border-zinc-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
            </div>
            <div>
                <label for="new_last_name" class="block text-sm font-medium text-zinc-700">Last name</label>
                <input id="new_last_name" type="text" name="last_name" value="{{ old('last_name') }}" required class="mt-1 w-full rounded-md border-zinc-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
            </div>
            <div class="sm:col-span-2">
                <label for="new_company" class="block text-sm font-medium text-zinc-700">Company</label>
                <input id="new_company" type="text" name="company" value="{{ old('company') }}" class="mt-1 w-full rounded-md border-zinc-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
            </div>
            <div class="sm:col-span-2">
                <label for="new_address1" class="block text-sm font-medium text-zinc-700">Address line 1</label>
                <input id="new_address1" type="text" name="address1" value="{{ old('address1') }}" required class="mt-1 w-full rounded-md border-zinc-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
            </div>
            <div class="sm:col-span-2">
                <label for="new_address2" class="block text-sm font-medium text-zinc-700">Address line 2</label>
                <input id="new_address2" type="text" name="address2" value="{{ old('address2') }}" class="mt-1 w-full rounded-md border-zinc-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
            </div>
            <div>
                <label for="new_city" class="block text-sm font-medium text-zinc-700">City</label>
                <input id="new_city" type="text" name="city" value="{{ old('city') }}" required class="mt-1 w-full rounded-md border-zinc-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
            </div>
            <div>
                <label for="new_zip" class="block text-sm font-medium text-zinc-700">Postal code</label>
                <input id="new_zip" type="text" name="zip" value="{{ old('zip') }}" required class="mt-1 w-full rounded-md border-zinc-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
            </div>
            <div>
                <label for="new_country" class="block text-sm font-medium text-zinc-700">Country</label>
                <input id="new_country" type="text" name="country" value="{{ old('country') }}" required class="mt-1 w-full rounded-md border-zinc-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
            </div>
            <div>
                <label for="new_country_code" class="block text-sm font-medium text-zinc-700">Country code</label>
                <input id="new_country_code" type="text" name="country_code" value="{{ old('country_code') }}" maxlength="2" required class="mt-1 w-full rounded-md border-zinc-300 text-sm uppercase focus:border-zinc-500 focus:ring-zinc-500">
            </div>
            <div>
                <label for="new_province" class="block text-sm font-medium text-zinc-700">State / Province</label>
                <input id="new_province" type="text" name="province" value="{{ old('province') }}" class="mt-1 w-full rounded-md border-zinc-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
            </div>
            <div>
                <label for="new_province_code" class="block text-sm font-medium text-zinc-700">State code</label>
                <input id="new_province_code" type="text" name="province_code" value="{{ old('province_code') }}" class="mt-1 w-full rounded-md border-zinc-300 text-sm uppercase focus:border-zinc-500 focus:ring-zinc-500">
            </div>
            <div class="sm:col-span-2">
                <label for="new_phone" class="block text-sm font-medium text-zinc-700">Phone</label>
                <input id="new_phone" type="tel" name="phone" value="{{ old('phone') }}" class="mt-1 w-full rounded-md border-zinc-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
            </div>
            <label class="sm:col-span-2 flex items-center gap-2 text-sm text-zinc-600">
                <input type="checkbox" name="is_default" value="1" class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500" @checked(old('is_default'))>
                Set as default address
            </label>
            <div class="sm:col-span-2">
                <button type="submit" class="rounded-md bg-zinc-900 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-700">Save address</button>
            </div>
        </form>
    </section>

    <section class="space-y-4">
        <h2 class="text-xl font-semibold text-zinc-900">Saved addresses</h2>

        @if ($addresses->isEmpty())
            <p class="rounded-xl border border-dashed border-zinc-300 bg-white px-6 py-8 text-sm text-zinc-600">No saved addresses yet.</p>
        @else
            <div class="grid gap-4 lg:grid-cols-2">
                @foreach ($addresses as $address)
                    @php($data = $address->address_json)
                    <article class="rounded-xl border {{ $address->is_default ? 'border-zinc-900' : 'border-zinc-200' }} bg-white p-5">
                        <header class="mb-3 flex items-center justify-between gap-3">
                            <h3 class="text-base font-semibold text-zinc-900">{{ $address->label ?: 'Address' }}</h3>
                            @if ($address->is_default)
                                <span class="rounded-full bg-zinc-900 px-2 py-1 text-xs font-semibold text-white">Default</span>
                            @endif
                        </header>

                        <div class="mb-4 space-y-1 text-sm text-zinc-700">
                            <p>{{ ($data['first_name'] ?? '').' '.($data['last_name'] ?? '') }}</p>
                            <p>{{ $data['address1'] ?? '' }}</p>
                            @if (! empty($data['address2']))
                                <p>{{ $data['address2'] }}</p>
                            @endif
                            <p>{{ $data['zip'] ?? '' }} {{ $data['city'] ?? '' }}</p>
                            <p>{{ $data['country'] ?? '' }}</p>
                        </div>

                        <details>
                            <summary class="cursor-pointer text-sm font-medium text-zinc-700 hover:text-zinc-900">Edit address</summary>
                            <form method="POST" action="{{ route('storefront.account.addresses.update', ['addressId' => $address->id]) }}" class="mt-3 grid gap-2 text-sm">
                                @csrf
                                @method('PUT')
                                <label class="block">Label
                                    <input type="text" name="label" value="{{ $address->label }}" class="mt-1 w-full rounded-md border-zinc-300 text-sm">
                                </label>
                                <label class="block">First name
                                    <input type="text" name="first_name" value="{{ $data['first_name'] ?? '' }}" class="mt-1 w-full rounded-md border-zinc-300 text-sm" required>
                                </label>
                                <label class="block">Last name
                                    <input type="text" name="last_name" value="{{ $data['last_name'] ?? '' }}" class="mt-1 w-full rounded-md border-zinc-300 text-sm" required>
                                </label>
                                <label class="block">Address line 1
                                    <input type="text" name="address1" value="{{ $data['address1'] ?? '' }}" class="mt-1 w-full rounded-md border-zinc-300 text-sm" required>
                                </label>
                                <label class="block">Address line 2
                                    <input type="text" name="address2" value="{{ $data['address2'] ?? '' }}" class="mt-1 w-full rounded-md border-zinc-300 text-sm">
                                </label>
                                <label class="block">City
                                    <input type="text" name="city" value="{{ $data['city'] ?? '' }}" class="mt-1 w-full rounded-md border-zinc-300 text-sm" required>
                                </label>
                                <label class="block">Postal code
                                    <input type="text" name="zip" value="{{ $data['zip'] ?? '' }}" class="mt-1 w-full rounded-md border-zinc-300 text-sm" required>
                                </label>
                                <label class="block">Country
                                    <input type="text" name="country" value="{{ $data['country'] ?? '' }}" class="mt-1 w-full rounded-md border-zinc-300 text-sm" required>
                                </label>
                                <label class="block">Country code
                                    <input type="text" name="country_code" value="{{ $data['country_code'] ?? '' }}" maxlength="2" class="mt-1 w-full rounded-md border-zinc-300 text-sm uppercase" required>
                                </label>
                                <label class="block">State / Province
                                    <input type="text" name="province" value="{{ $data['province'] ?? '' }}" class="mt-1 w-full rounded-md border-zinc-300 text-sm">
                                </label>
                                <label class="block">State code
                                    <input type="text" name="province_code" value="{{ $data['province_code'] ?? '' }}" class="mt-1 w-full rounded-md border-zinc-300 text-sm uppercase">
                                </label>
                                <label class="block">Phone
                                    <input type="text" name="phone" value="{{ $data['phone'] ?? '' }}" class="mt-1 w-full rounded-md border-zinc-300 text-sm">
                                </label>
                                <label class="flex items-center gap-2 text-sm text-zinc-600">
                                    <input type="checkbox" name="is_default" value="1" class="rounded border-zinc-300 text-zinc-900" @checked($address->is_default)>
                                    Set as default
                                </label>
                                <button type="submit" class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100">Update</button>
                            </form>
                        </details>

                        <div class="mt-3 flex flex-wrap gap-2">
                            @if (! $address->is_default)
                                <form method="POST" action="{{ route('storefront.account.addresses.default', ['addressId' => $address->id]) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="rounded-md border border-zinc-300 px-3 py-2 text-xs font-semibold text-zinc-700 hover:bg-zinc-100">Set as default</button>
                                </form>
                            @endif

                            <form method="POST" action="{{ route('storefront.account.addresses.destroy', ['addressId' => $address->id]) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-md border border-rose-300 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50">Delete</button>
                            </form>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</section>
@endsection
