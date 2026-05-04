<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Set a new password')" :description="__('Choose a new password for your account')" />

    <form wire:submit="resetPassword" class="flex flex-col gap-6">
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
            :label="__('New password')"
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

        <flux:button variant="primary" type="submit" class="w-full" data-test="customer-reset-password-button">
            {{ __('Reset password') }}
        </flux:button>
    </form>

    <div class="text-center text-sm text-zinc-600 dark:text-zinc-400">
        <flux:link :href="route('account.login')" wire:navigate>{{ __('Back to log in') }}</flux:link>
    </div>
</div>
