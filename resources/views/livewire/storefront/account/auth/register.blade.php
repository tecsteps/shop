<div class="mx-auto flex max-w-md flex-col gap-6 px-4 py-16 sm:px-6">
    <div class="text-center">
        <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ __('Create an account') }}</h1>
        <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Enter your details below to create your account') }}</p>
    </div>

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
            placeholder="email@example.com"
        />

        <flux:input
            wire:model="password"
            :label="__('Password')"
            type="password"
            required
            autocomplete="new-password"
            :placeholder="__('Password')"
            viewable
        />

        <flux:input
            wire:model="password_confirmation"
            :label="__('Confirm password')"
            type="password"
            required
            autocomplete="new-password"
            :placeholder="__('Confirm password')"
            viewable
        />

        <flux:checkbox wire:model="marketing_opt_in" :label="__('Subscribe to marketing emails')" />

        <flux:button variant="primary" type="submit" class="w-full">
            {{ __('Create account') }}
        </flux:button>
    </form>

    @if (Route::has('account.login'))
        <div class="space-x-1 text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Already have an account?') }}</span>
            <flux:link :href="route('account.login')" wire:navigate>{{ __('Log in') }}</flux:link>
        </div>
    @endif
</div>
