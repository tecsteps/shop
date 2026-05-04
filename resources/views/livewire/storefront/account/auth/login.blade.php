<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Log in')" :description="__('Access your orders and saved addresses')" />

    <form wire:submit="login" class="flex flex-col gap-6">
        <flux:input
            wire:model="email"
            :label="__('Email address')"
            type="email"
            required
            autofocus
            autocomplete="email"
            placeholder="customer@acme.test"
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

        <div class="-mt-4 text-right text-sm">
            <flux:link :href="route('account.password.request')" wire:navigate>{{ __('Forgot your password?') }}</flux:link>
        </div>

        <flux:checkbox wire:model="remember" :label="__('Remember me')" />

        <flux:button variant="primary" type="submit" class="w-full" data-test="customer-login-button">
            {{ __('Log in') }}
        </flux:button>
    </form>

    <div class="text-center text-sm text-zinc-600 dark:text-zinc-400">
        <span>{{ __('New customer?') }}</span>
        <flux:link :href="route('account.register')" wire:navigate>{{ __('Create an account') }}</flux:link>
    </div>
</div>
