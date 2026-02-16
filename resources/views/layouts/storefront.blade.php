<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased">
        <div class="flex min-h-svh flex-col">
            {{ $slot }}
        </div>
        @fluxScripts
    </body>
</html>
