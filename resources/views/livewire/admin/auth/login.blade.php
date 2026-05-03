<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Sign in')" :description="__('Use your staff account to manage this store')" />

    <form wire:submit="login" class="flex flex-col gap-6">
        <flux:input
            wire:model="email"
            :label="__('Email address')"
            type="email"
            required
            autofocus
            autocomplete="email"
            placeholder="admin@acme.test"
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

        <flux:button variant="primary" type="submit" class="w-full" data-test="admin-login-button">
            {{ __('Sign in') }}
        </flux:button>
    </form>
</div>
