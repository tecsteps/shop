<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Reset password')" :description="__('Enter your email and we will send a reset link')" />

    @if (session('status'))
        <flux:callout variant="success" icon="check-circle">
            {{ session('status') }}
        </flux:callout>
    @endif

    <form wire:submit="send" class="flex flex-col gap-6">
        <flux:input
            wire:model="email"
            :label="__('Email address')"
            type="email"
            required
            autofocus
            autocomplete="email"
            placeholder="customer@acme.test"
        />

        <flux:button variant="primary" type="submit" class="w-full" data-test="customer-forgot-password-button">
            {{ __('Send reset link') }}
        </flux:button>
    </form>

    <div class="text-center text-sm text-zinc-600 dark:text-zinc-400">
        <flux:link :href="route('account.login')" wire:navigate>{{ __('Back to log in') }}</flux:link>
    </div>
</div>
