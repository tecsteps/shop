<div class="max-w-md mx-auto px-4 py-16">
    <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Create Account</h1>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            Fill in your details to create a new account
        </p>
    </div>

    <form wire:submit="register" class="flex flex-col gap-6">
        <div class="grid grid-cols-2 gap-4">
            <flux:input
                wire:model="first_name"
                label="First name"
                type="text"
                required
                autofocus
                autocomplete="given-name"
            />

            <flux:input
                wire:model="last_name"
                label="Last name"
                type="text"
                required
                autocomplete="family-name"
            />
        </div>

        <flux:input
            wire:model="email"
            label="Email address"
            type="email"
            required
            autocomplete="email"
            placeholder="email@example.com"
        />

        <flux:input
            wire:model="password"
            label="Password"
            type="password"
            required
            autocomplete="new-password"
            viewable
        />

        <flux:input
            wire:model="password_confirmation"
            label="Confirm password"
            type="password"
            required
            autocomplete="new-password"
            viewable
        />

        <flux:button variant="primary" type="submit" class="w-full">
            Create account
        </flux:button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-600 dark:text-gray-400">
        Already have an account?
        <a href="{{ route('storefront.account.login') }}" class="text-blue-600 dark:text-blue-400 hover:underline" wire:navigate>
            Log in
        </a>
    </p>
</div>
