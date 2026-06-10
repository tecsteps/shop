<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ __('Your Addresses') }}</h1>
        <button
            type="button"
            wire:click="create"
            class="rounded-lg px-5 py-2.5 text-sm font-semibold text-white transition hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600"
            style="background-color: var(--sf-primary, #2563eb);"
            data-test="add-address-button"
        >
            + {{ __('Add new address') }}
        </button>
    </div>

    <x-storefront.account-nav current="addresses" class="mt-6" />

    @if ($statusMessage !== null)
        <p class="mt-6 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700 dark:bg-green-950/50 dark:text-green-400" role="status">
            {{ $statusMessage }}
        </p>
    @endif

    @if ($addresses->isEmpty())
        <div class="flex flex-col items-center justify-center py-24 text-center">
            <svg class="size-20 text-zinc-300 dark:text-zinc-700" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
            </svg>
            <p class="mt-4 text-zinc-500 dark:text-zinc-400">{{ __("You haven't saved any addresses yet.") }}</p>
        </div>
    @else
        <ul class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3" role="list">
            @foreach ($addresses as $address)
                @php
                    $json = $address->address_json ?? [];
                @endphp
                <li
                    wire:key="address-{{ $address->getKey() }}"
                    @class([
                        'flex flex-col rounded-2xl border p-5',
                        'border-(--sf-primary,#2563eb) ring-1 ring-(--sf-primary,#2563eb)' => $address->is_default,
                        'border-zinc-200 dark:border-zinc-800' => ! $address->is_default,
                    ])
                >
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $address->label ?: __('Address') }}</span>
                        @if ($address->is_default)
                            <x-storefront.badge :text="__('Default')" variant="new" />
                        @endif
                    </div>

                    <address class="mt-3 flex-1 text-sm text-zinc-600 not-italic dark:text-zinc-400">
                        {{ trim(($json['first_name'] ?? '').' '.($json['last_name'] ?? '')) }}<br />
                        {{ $json['address1'] ?? '' }}<br />
                        @if (filled($json['address2'] ?? null))
                            {{ $json['address2'] }}<br />
                        @endif
                        {{ trim(($json['zip'] ?? '').' '.($json['city'] ?? '')) }}@if (filled($json['province'] ?? null)), {{ $json['province'] }}@endif<br />
                        {{ \App\Support\Storefront\Countries::name($json['country_code'] ?? '') }}
                        @if (filled($json['phone'] ?? null))
                            <br />{{ $json['phone'] }}
                        @endif
                    </address>

                    <div class="mt-4 flex flex-wrap items-center gap-4 border-t border-zinc-100 pt-3 text-sm dark:border-zinc-800">
                        <button
                            type="button"
                            wire:click="edit({{ $address->getKey() }})"
                            class="rounded font-medium text-blue-600 transition hover:text-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:text-blue-400"
                        >
                            {{ __('Edit') }}
                        </button>
                        <button
                            type="button"
                            wire:click="delete({{ $address->getKey() }})"
                            wire:confirm="{{ __('Delete this address?') }}"
                            class="rounded font-medium text-red-600 transition hover:text-red-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-600 dark:text-red-400"
                        >
                            {{ __('Delete') }}
                        </button>
                        @unless ($address->is_default)
                            <button
                                type="button"
                                wire:click="setDefault({{ $address->getKey() }})"
                                class="rounded font-medium text-zinc-600 transition hover:text-zinc-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:text-zinc-400 dark:hover:text-white"
                            >
                                {{ __('Set as default') }}
                            </button>
                        @endunless
                    </div>
                </li>
            @endforeach
        </ul>
    @endif

    {{-- Add / edit modal --}}
    @if ($showForm)
        <div
            class="fixed inset-0 z-50 overflow-y-auto"
            role="dialog"
            aria-modal="true"
            aria-labelledby="address-form-heading"
            x-data
            x-init="$nextTick(() => $el.querySelector('input, select, textarea, button')?.focus())"
            x-on:keydown.escape.window="$wire.set('showForm', false)"
        >
            <div class="fixed inset-0 bg-zinc-950/50" wire:click="$set('showForm', false)" aria-hidden="true"></div>
            <div class="relative mx-auto my-8 w-full max-w-2xl px-4">
                <div class="rounded-2xl bg-white p-6 shadow-xl sm:p-8 dark:bg-zinc-900">
                    <div class="flex items-center justify-between gap-4">
                        <h2 id="address-form-heading" class="text-lg font-semibold text-zinc-900 dark:text-white">
                            {{ $editingId !== null ? __('Edit address') : __('Add new address') }}
                        </h2>
                        <button
                            type="button"
                            wire:click="$set('showForm', false)"
                            class="rounded-lg p-2 text-zinc-500 transition hover:bg-zinc-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:text-zinc-400 dark:hover:bg-zinc-800"
                            aria-label="{{ __('Close') }}"
                        >
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form wire:submit="save" class="mt-6 space-y-4">
                        <div>
                            <label for="address-label" class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Label') }}</label>
                            <input
                                id="address-label"
                                type="text"
                                wire:model="label"
                                placeholder="{{ __('e.g. Home, Work') }}"
                                class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 placeholder-zinc-400 transition focus:border-blue-600 focus:ring-2 focus:ring-blue-600/30 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900 dark:text-white dark:placeholder-zinc-500"
                            />
                            @error('label')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <x-storefront.address-form prefix="form" :address="$form" />

                        <div class="flex items-center justify-end gap-3 pt-2">
                            <button
                                type="button"
                                wire:click="$set('showForm', false)"
                                class="rounded-lg border border-zinc-300 px-5 py-2.5 text-sm font-medium text-zinc-700 transition hover:bg-zinc-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
                            >
                                {{ __('Cancel') }}
                            </button>
                            <button
                                type="submit"
                                class="rounded-lg px-5 py-2.5 text-sm font-semibold text-white transition hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600"
                                style="background-color: var(--sf-primary, #2563eb);"
                                data-test="save-address-button"
                            >
                                <span wire:loading.remove wire:target="save">{{ __('Save address') }}</span>
                                <span wire:loading wire:target="save">{{ __('Saving...') }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
