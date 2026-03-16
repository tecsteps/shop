<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-neutral-950">
        <div class="flex min-h-svh flex-col">
            <main class="flex-1">
                {{ $slot }}
            </main>
        </div>
        @fluxScripts
    </body>
</html>
