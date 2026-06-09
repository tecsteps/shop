<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-neutral-950">
        <div class="mx-auto w-full max-w-3xl p-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-semibold">{{ __('Dashboard') }}</h1>

                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <flux:button type="submit" variant="ghost" data-test="admin-logout-button">
                        {{ __('Log out') }}
                    </flux:button>
                </form>
            </div>

            <p class="mt-4 text-zinc-600 dark:text-zinc-400">
                {{ __('Current store:') }} {{ $currentStore->name }}
            </p>
        </div>
        @fluxScripts
    </body>
</html>
