<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12">
    <x-storefront.breadcrumbs :items="[
        ['label' => 'Home', 'url' => route('storefront.home')],
        ['label' => 'Login'],
    ]" class="mb-8" />

    <div class="mx-auto max-w-md">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ __('Customer Login') }}</h1>
        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Sign in to your account') }}</p>

        <form wire:submit="login" class="mt-8 flex flex-col gap-6">
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

        <div class="mt-6 space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Don\'t have an account?') }}</span>
            <flux:link :href="route('customer.register')" wire:navigate>{{ __('Sign up') }}</flux:link>
        </div>
    </div>
</div>
