<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Create an account')" :description="__('Save your details for faster checkout')" />

    <form wire:submit="register" class="flex flex-col gap-6">
        <flux:input
            wire:model="name"
            :label="__('Name')"
            type="text"
            required
            autofocus
            autocomplete="name"
        />

        <flux:input
            wire:model="email"
            :label="__('Email address')"
            type="email"
            required
            autocomplete="email"
            placeholder="customer@acme.test"
        />

        <flux:input
            wire:model="password"
            :label="__('Password')"
            type="password"
            required
            autocomplete="new-password"
            viewable
        />

        <flux:input
            wire:model="password_confirmation"
            :label="__('Confirm password')"
            type="password"
            required
            autocomplete="new-password"
            viewable
        />

        <flux:checkbox wire:model="marketing_opt_in" :label="__('Email me about new products and offers')" />

        <flux:button variant="primary" type="submit" class="w-full" data-test="customer-register-button">
            {{ __('Create account') }}
        </flux:button>
    </form>

    <div class="text-center text-sm text-zinc-600 dark:text-zinc-400">
        <span>{{ __('Already have an account?') }}</span>
        <flux:link :href="route('account.login')" wire:navigate>{{ __('Log in') }}</flux:link>
    </div>
</div>
