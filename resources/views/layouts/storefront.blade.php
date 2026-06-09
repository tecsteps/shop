<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-neutral-950">
        <header class="border-b border-zinc-200 p-4 dark:border-zinc-700">
            <a href="{{ route('home') }}" class="font-semibold">
                {{ $currentStore->name ?? config('app.name') }}
            </a>
        </header>
        <main class="mx-auto w-full max-w-md p-6">
            {{ $slot }}
        </main>
        @fluxScripts
    </body>
</html>
