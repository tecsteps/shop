<div class="max-w-md mx-auto px-4 py-16">
    <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Log in</h1>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            Enter your email and password to access your account
        </p>
    </div>

    <form wire:submit="login" class="flex flex-col gap-6">
        <flux:input
            wire:model="email"
            label="Email address"
            type="email"
            required
            autofocus
            autocomplete="email"
            placeholder="email@example.com"
        />

        <flux:input
            wire:model="password"
            label="Password"
            type="password"
            required
            autocomplete="current-password"
            placeholder="Password"
            viewable
        />

        <flux:checkbox wire:model="remember" label="Remember me" />

        <flux:button variant="primary" type="submit" class="w-full">
            Log in
        </flux:button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-600 dark:text-gray-400">
        Don't have an account?
        <a href="{{ route('storefront.account.register') }}" class="text-blue-600 dark:text-blue-400 hover:underline" wire:navigate>
            Create account
        </a>
    </p>
</div>
