<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12">
    <x-storefront.breadcrumbs :items="[
        ['label' => 'Home', 'url' => route('storefront.home')],
        ['label' => 'Create Account'],
    ]" class="mb-8" />

    <div class="mx-auto max-w-md">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ __('Create an account') }}</h1>
        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Enter your details below to create your account') }}</p>

        <form wire:submit="register" class="mt-8 flex flex-col gap-6">
            <flux:input
                wire:model="name"
                :label="__('Name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                :placeholder="__('Full name')"
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

            <flux:checkbox wire:model="marketing_opt_in" :label="__('I want to receive marketing emails')" />

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full">
                    {{ __('Create account') }}
                </flux:button>
            </div>
        </form>

        <div class="mt-6 space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Already have an account?') }}</span>
            <flux:link :href="route('customer.login')" wire:navigate>{{ __('Log in') }}</flux:link>
        </div>
    </div>
</div>
