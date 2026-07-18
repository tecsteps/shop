<div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
    <x-storefront.breadcrumbs :items="[
        ['label' => 'Account', 'url' => route('storefront.account.dashboard')],
        ['label' => 'Addresses', 'url' => null],
    ]" />

    <div class="mt-4 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Your Addresses</h1>
        <flux:button wire:click="addNew" variant="primary" icon="plus">Add new address</flux:button>
    </div>

    @if ($this->addresses->isEmpty())
        <p class="mt-8 text-sm text-zinc-500 dark:text-zinc-400">You haven't saved any addresses yet.</p>
    @else
        <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($this->addresses as $address)
                @php $data = $address->address_json; @endphp
                <div wire:key="address-{{ $address->id }}" class="rounded-xl border p-5 {{ $address->is_default ? 'border-blue-600 ring-1 ring-blue-600' : 'border-zinc-200 dark:border-zinc-800' }}">
                    @if ($address->is_default)
                        <x-storefront.badge text="Default" variant="new" class="mb-2" />
                    @endif
                    <address class="text-sm text-zinc-700 not-italic dark:text-zinc-300">
                        {{ $data['first_name'] ?? '' }} {{ $data['last_name'] ?? '' }}<br />
                        {{ $data['address1'] ?? '' }}<br />
                        @if (! empty($data['address2']))
                            {{ $data['address2'] }}<br />
                        @endif
                        {{ $data['postal_code'] ?? '' }} {{ $data['city'] ?? '' }}<br />
                        {{ $data['country'] ?? '' }}
                    </address>

                    <div class="mt-4 flex flex-wrap gap-3 text-sm">
                        <button type="button" wire:click="edit({{ $address->id }})" class="text-blue-600 hover:underline dark:text-blue-400">Edit</button>
                        <button type="button" wire:click="delete({{ $address->id }})" wire:confirm="Delete this address?" class="text-red-600 hover:underline dark:text-red-400">Delete</button>
                        @if (! $address->is_default)
                            <button type="button" wire:click="setDefault({{ $address->id }})" class="text-zinc-600 hover:underline dark:text-zinc-400">Set as default</button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <flux:modal wire:model.self="showModal" class="md:w-96">
        <form wire:submit.prevent="save" class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? 'Edit address' : 'Add new address' }}</flux:heading>

            <flux:field>
                <flux:label for="addr-label">Label</flux:label>
                <flux:input id="addr-label" wire:model="label" placeholder="Home, Work, etc." />
            </flux:field>

            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label for="addr-firstName">First name</flux:label>
                    <flux:input id="addr-firstName" wire:model="firstName" required />
                    <flux:error name="firstName" />
                </flux:field>
                <flux:field>
                    <flux:label for="addr-lastName">Last name</flux:label>
                    <flux:input id="addr-lastName" wire:model="lastName" required />
                    <flux:error name="lastName" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label for="addr-address1">Address line 1</flux:label>
                <flux:input id="addr-address1" wire:model="address1" required />
                <flux:error name="address1" />
            </flux:field>

            <flux:field>
                <flux:label for="addr-address2">Address line 2</flux:label>
                <flux:input id="addr-address2" wire:model="address2" />
            </flux:field>

            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label for="addr-city">City</flux:label>
                    <flux:input id="addr-city" wire:model="city" required />
                    <flux:error name="city" />
                </flux:field>
                <flux:field>
                    <flux:label for="addr-province">State / Province</flux:label>
                    <flux:input id="addr-province" wire:model="province" />
                </flux:field>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label for="addr-postalCode">Postal code</flux:label>
                    <flux:input id="addr-postalCode" wire:model="postalCode" required />
                    <flux:error name="postalCode" />
                </flux:field>
                <flux:field>
                    <flux:label for="addr-country">Country</flux:label>
                    <flux:select id="addr-country" wire:model="country" required>
                        <option value="DE">Germany</option>
                        <option value="AT">Austria</option>
                        <option value="CH">Switzerland</option>
                        <option value="US">United States</option>
                        <option value="GB">United Kingdom</option>
                        <option value="FR">France</option>
                    </flux:select>
                </flux:field>
            </div>

            <flux:field>
                <flux:label for="addr-phone">Phone</flux:label>
                <flux:input id="addr-phone" type="tel" wire:model="phone" />
            </flux:field>

            <div class="flex justify-end gap-2">
                <flux:button type="button" variant="ghost" wire:click="$set('showModal', false)">Cancel</flux:button>
                <flux:button type="submit" variant="primary">Save address</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
