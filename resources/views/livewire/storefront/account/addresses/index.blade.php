<div class="space-y-10">
    <header class="space-y-2">
        <a href="{{ route('storefront.account.dashboard') }}" class="text-xs text-zinc-500 underline-offset-2 hover:underline dark:text-zinc-400">Back to account</a>
        <h1 class="text-3xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-50 sm:text-4xl">Addresses</h1>
    </header>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Saved addresses</h2>

        @if ($addresses->isEmpty())
            <div class="rounded-2xl border border-dashed border-zinc-300 bg-zinc-50 p-8 text-center dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-sm text-zinc-600 dark:text-zinc-400">No addresses saved yet.</p>
            </div>
        @else
            <div class="grid gap-4 md:grid-cols-2">
                @foreach ($addresses as $address)
                    @php($data = $address->address_json ?? [])
                    <div class="space-y-3 rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900" wire:key="address-{{ $address->id }}">
                        <div class="flex items-start justify-between">
                            <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $address->label }}</h3>
                            @if ($address->is_default)
                                <span class="rounded-full bg-zinc-900 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-white dark:bg-white dark:text-zinc-900">Default</span>
                            @endif
                        </div>
                        <address class="not-italic text-sm text-zinc-600 dark:text-zinc-400">
                            {{ trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? '')) }}<br />
                            @if (! empty($data['address1'])) {{ $data['address1'] }}<br /> @endif
                            @if (! empty($data['city'])) {{ $data['city'] }} {{ $data['postal_code'] ?? '' }}<br /> @endif
                            {{ $data['country'] ?? '' }}
                        </address>
                        <div class="flex items-center gap-3">
                            @unless ($address->is_default)
                                <button type="button" wire:click="makeDefault({{ $address->id }})" class="text-xs font-medium text-zinc-600 underline-offset-2 hover:text-zinc-900 hover:underline dark:text-zinc-400 dark:hover:text-zinc-100">Set default</button>
                            @endunless
                            <button type="button" wire:click="deleteAddress({{ $address->id }})" class="text-xs font-medium text-red-600 underline-offset-2 hover:underline dark:text-red-400">Delete</button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <section class="space-y-4">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Add a new address</h2>

        <form wire:submit.prevent="addAddress" class="space-y-5 rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="block text-sm">
                    <span class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">Label</span>
                    <input type="text" wire:model="label" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                    @error('label') <span class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </label>
                <label class="block text-sm">
                    <span class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">Country</span>
                    <select wire:model="country" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                        <option value="DE">Germany</option>
                        <option value="AT">Austria</option>
                        <option value="CH">Switzerland</option>
                        <option value="FR">France</option>
                        <option value="NL">Netherlands</option>
                        <option value="US">United States</option>
                        <option value="GB">United Kingdom</option>
                    </select>
                </label>
                <label class="block text-sm">
                    <span class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">First name</span>
                    <input type="text" wire:model="firstName" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                    @error('firstName') <span class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </label>
                <label class="block text-sm">
                    <span class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">Last name</span>
                    <input type="text" wire:model="lastName" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                    @error('lastName') <span class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </label>
                <label class="block text-sm sm:col-span-2">
                    <span class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">Address</span>
                    <input type="text" wire:model="line1" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                    @error('line1') <span class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </label>
                <label class="block text-sm sm:col-span-2">
                    <span class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">Apartment, suite (optional)</span>
                    <input type="text" wire:model="line2" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                </label>
                <label class="block text-sm">
                    <span class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">City</span>
                    <input type="text" wire:model="city" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                    @error('city') <span class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </label>
                <label class="block text-sm">
                    <span class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">Postal code</span>
                    <input type="text" wire:model="postalCode" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                    @error('postalCode') <span class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                </label>
            </div>

            <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                <input type="checkbox" wire:model="isDefault" class="h-4 w-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900 dark:border-zinc-700" />
                <span>Use as default shipping address</span>
            </label>

            <button type="submit" class="inline-flex rounded-full bg-zinc-900 px-6 py-3 text-sm font-semibold text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                Save address
            </button>
        </form>
    </section>
</div>
