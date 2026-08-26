<div>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="lg:grid lg:grid-cols-[240px_minmax(0,1fr)] lg:gap-10">
            <aside class="hidden lg:block">
                <div class="sticky top-24">
                    @include('storefront.partials.account-nav')
                </div>
            </aside>

            <div>
                <div class="lg:hidden">
                    @include('storefront.partials.account-nav')
                </div>

                <div class="mt-6 flex flex-wrap items-center justify-between gap-3 lg:mt-0">
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl dark:text-white">Your Addresses</h1>
                    <button
                        type="button"
                        wire:click="openCreate"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-400"
                    >
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 5v14M5 12h14" />
                        </svg>
                        Add new address
                    </button>
                </div>

                @if ($this->addresses === [])
                    <div class="mt-8 rounded-2xl border border-zinc-200 p-10 text-center dark:border-zinc-800">
                        <p class="text-base font-semibold text-zinc-900 dark:text-white">No saved addresses</p>
                        <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Add an address to speed up checkout.</p>
                    </div>
                @else
                    <div class="mt-8 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($this->addresses as $entry)
                            @php
                                $address = $entry['address'];
                            @endphp
                            <div class="rounded-2xl border p-5 transition {{ $entry['is_default'] ? 'border-blue-500 ring-1 ring-blue-500 dark:border-blue-500' : 'border-zinc-200 dark:border-zinc-800' }}">
                                <div class="flex items-start justify-between gap-3">
                                    <p class="text-sm font-semibold text-zinc-900 dark:text-white">
                                        {{ $address['first_name'] ?? '' }} {{ $address['last_name'] ?? '' }}
                                    </p>
                                    @if ($entry['is_default'])
                                        <x-storefront-badge text="Default" variant="info" />
                                    @endif
                                </div>
                                <address class="mt-2 space-y-0.5 text-sm not-italic text-zinc-600 dark:text-zinc-300">
                                    <p>{{ $address['address1'] ?? '' }}</p>
                                    @if (! empty($address['address2']))
                                        <p>{{ $address['address2'] }}</p>
                                    @endif
                                    <p>{{ $address['city'] ?? '' }}{{ ! empty($address['province']) ? ', '.$address['province'] : '' }} {{ $address['postal_code'] ?? '' }}</p>
                                    <p>{{ $address['country'] ?? '' }}</p>
                                    @if (! empty($address['phone']))
                                        <p class="pt-1 text-xs text-zinc-400 dark:text-zinc-500">{{ $address['phone'] }}</p>
                                    @endif
                                </address>
                                <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 border-t border-zinc-100 pt-4 text-sm dark:border-zinc-800">
                                    <button type="button" wire:click="openEdit({{ $entry['id'] }})" class="font-medium text-blue-600 transition hover:underline dark:text-blue-400">
                                        Edit
                                    </button>
                                    <button type="button" wire:click="confirmDelete({{ $entry['id'] }})" class="font-medium text-red-600 transition hover:underline dark:text-red-400">
                                        Delete
                                    </button>
                                    @if (! $entry['is_default'])
                                        <button type="button" wire:click="setDefault({{ $entry['id'] }})" class="font-medium text-zinc-500 transition hover:text-zinc-900 hover:underline dark:text-zinc-400 dark:hover:text-white">
                                            Set as default
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Address form modal --}}
    <div
        class="fixed inset-0 z-50"
        wire:key="address-form-modal"
        x-data="{}"
        x-effect="if ($wire.showForm) { document.body.classList.add('overflow-hidden'); } else { document.body.classList.remove('overflow-hidden'); }"
        @keydown.escape.window="$wire.closeForm()"
    >
        <div
            x-show="$wire.showForm"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="absolute inset-0 bg-zinc-950/50 backdrop-blur-sm"
            @click="$wire.closeForm()"
            aria-hidden="true"
        ></div>

        <div
            x-show="$wire.showForm"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="absolute inset-x-0 top-0 mx-auto mt-10 w-[calc(100%-2rem)] max-w-lg rounded-2xl bg-white shadow-2xl dark:bg-zinc-950"
            role="dialog"
            aria-modal="true"
            aria-label="Address form"
        >
            <div class="flex items-center justify-between border-b border-zinc-200 px-5 py-4 dark:border-zinc-800">
                <h2 class="text-base font-semibold text-zinc-900 dark:text-white">
                    {{ $this->editingId ? 'Edit address' : 'Add new address' }}
                </h2>
                <button
                    type="button"
                    @click="$wire.closeForm()"
                    aria-label="Close"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-900 dark:hover:bg-zinc-800 dark:hover:text-white"
                >
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form wire:submit="save" class="max-h-[70vh] space-y-4 overflow-y-auto px-5 py-5">
                <div>
                    <label for="address-label" class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        Label <span class="text-zinc-400 dark:text-zinc-500">(optional)</span>
                    </label>
                    <input
                        id="address-label"
                        type="text"
                        wire:model="form.label"
                        placeholder="Home, Work, ..."
                        class="block w-full rounded-lg border border-zinc-300 bg-white px-3.5 py-2.5 text-sm text-zinc-900 placeholder-zinc-400 transition focus:border-zinc-900 focus:outline-none focus:ring-2 focus:ring-zinc-900/20 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white dark:placeholder-zinc-500 dark:focus:border-white dark:focus:ring-white/20"
                    />
                </div>

                <x-storefront-address-form :address="$this->form" prefix="form" />

                <label class="flex items-center gap-2.5 text-sm text-zinc-700 dark:text-zinc-300">
                    <input
                        type="checkbox"
                        wire:model="setAsDefault"
                        class="size-4 rounded border-zinc-300 text-blue-600 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-900 dark:ring-offset-zinc-950"
                    />
                    Set as default address
                </label>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button
                        type="button"
                        @click="$wire.closeForm()"
                        class="rounded-lg border border-zinc-300 px-5 py-2.5 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-900"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-400"
                    >
                        Save address
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Delete confirmation --}}
    <div
        class="fixed inset-0 z-50"
        wire:key="address-delete-modal"
        @keydown.escape.window="$wire.cancelDelete()"
    >
        <div
            x-show="$wire.deleteTarget !== null"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            class="absolute inset-0 bg-zinc-950/50 backdrop-blur-sm"
            @click="$wire.cancelDelete()"
            aria-hidden="true"
        ></div>

        <div
            x-show="$wire.deleteTarget !== null"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            class="absolute inset-x-0 top-0 mx-auto mt-40 w-[calc(100%-2rem)] max-w-sm rounded-2xl bg-white p-6 text-center shadow-2xl dark:bg-zinc-950"
            role="alertdialog"
            aria-modal="true"
            aria-label="Delete address"
        >
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Delete this address?</h2>
            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">This action cannot be undone.</p>
            <div class="mt-6 flex items-center justify-center gap-3">
                <button
                    type="button"
                    @click="$wire.cancelDelete()"
                    class="rounded-lg border border-zinc-300 px-5 py-2.5 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-900"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    wire:click="delete"
                    class="rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700"
                >
                    Delete
                </button>
            </div>
        </div>
    </div>
</div>
