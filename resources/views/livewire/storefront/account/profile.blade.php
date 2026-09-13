<div class="mx-auto max-w-2xl px-4 py-8">
    <x-storefront.breadcrumbs :items="[['label' => 'Account', 'url' => route('customer.account')], ['label' => 'Profile']]" />
    <h1 class="mt-4 text-3xl font-bold">Profile</h1>

    @if ($status)
        <p class="mt-4 rounded-lg bg-green-50 p-3 text-sm text-green-700 dark:bg-green-950/40 dark:text-green-300" role="status">{{ $status }}</p>
    @endif

    <form wire:submit="save" class="mt-6 flex flex-col gap-4">
        <div>
            <flux:input wire:model="name" :label="__('Name')" autocomplete="name" />
            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <flux:input wire:model="email" type="email" :label="__('Email')" required autocomplete="email" />
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" wire:model="marketingOptIn" class="h-4 w-4" />
            Subscribe to marketing emails
        </label>
        <flux:button type="submit" variant="primary" wire:loading.attr="disabled">Save changes</flux:button>
    </form>
</div>
