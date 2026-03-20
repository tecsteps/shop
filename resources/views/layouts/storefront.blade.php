<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:header class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">{{ $currentStore->name ?? config('app.name') }}</flux:heading>

            <flux:spacer />

            @if(auth('customer')->check())
                <flux:dropdown position="bottom" align="end">
                    <flux:profile
                        :name="auth('customer')->user()->name"
                        icon-trailing="chevron-down"
                    />

                    <flux:menu>
                        <flux:menu.item :href="route('storefront.account')">
                            {{ __('My Account') }}
                        </flux:menu.item>

                        <flux:menu.separator />

                        <form method="POST" action="{{ route('storefront.logout') }}" class="w-full">
                            @csrf
                            <flux:menu.item
                                as="button"
                                type="submit"
                                icon="arrow-right-start-on-rectangle"
                                class="w-full cursor-pointer"
                            >
                                {{ __('Log Out') }}
                            </flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            @else
                <flux:button :href="route('storefront.login')" variant="ghost" size="sm">
                    {{ __('Log In') }}
                </flux:button>
            @endif
        </flux:header>

        <flux:main>
            {{ $slot }}
        </flux:main>

        @fluxScripts
    </body>
</html>
