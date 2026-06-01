<div class="mx-auto flex max-w-md flex-col gap-6 px-4 py-16 sm:px-6">
    <div class="text-center">
        <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ __('Log in to your account') }}</h1>
        <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Enter your email and password below to log in') }}</p>
    </div>

    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="login" class="flex flex-col gap-6">
        <flux:input
            wire:model="email"
            :label="__('Email address')"
            type="email"
            required
            autofocus
            autocomplete="email"
            placeholder="email@example.com"
        />

        <flux:input
            wire:model="password"
            :label="__('Password')"
            type="password"
            required
            autocomplete="current-password"
            :placeholder="__('Password')"
            viewable
        />

        <flux:checkbox wire:model="remember" :label="__('Remember me')" />

        <flux:button variant="primary" type="submit" class="w-full">
            {{ __('Log in') }}
        </flux:button>
    </form>

    @if (Route::has('account.register'))
        <div class="space-x-1 text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __("Don't have an account?") }}</span>
            <flux:link :href="route('account.register')" wire:navigate>{{ __('Create one') }}</flux:link>
        </div>
    @endif
</div>
