<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Log in to your account')" :description="__('Enter your email and password below to log in')" />

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

        <div class="flex items-center justify-end">
            <flux:button variant="primary" type="submit" class="w-full">
                {{ __('Log in') }}
            </flux:button>
        </div>
    </form>

    @if (Route::has('account.register'))
        <div class="space-x-1 text-sm text-center text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Don\'t have an account?') }}</span>
            <flux:link :href="route('account.register')" wire:navigate>{{ __('Sign up') }}</flux:link>
        </div>
    @endif
</div>
