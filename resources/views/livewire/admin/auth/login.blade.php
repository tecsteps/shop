<div class="flex flex-col gap-6">
    <div class="flex flex-col items-center gap-2 text-center">
        <h1 class="text-xl font-semibold">{{ __('Admin sign in') }}</h1>
        <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Enter your email and password to access the admin panel') }}</p>
    </div>

    <form wire:submit="login" class="flex flex-col gap-6">
        <flux:input
            wire:model="email"
            :label="__('Email address')"
            type="email"
            required
            autofocus
            autocomplete="email"
            data-test="admin-login-email"
        />

        <flux:input
            wire:model="password"
            :label="__('Password')"
            type="password"
            required
            autocomplete="current-password"
            data-test="admin-login-password"
        />

        <flux:checkbox wire:model="remember" :label="__('Remember me')" />

        <flux:button variant="primary" type="submit" class="w-full" data-test="admin-login-button">
            {{ __('Log in') }}
        </flux:button>
    </form>
</div>
