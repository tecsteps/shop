@php
    $store = app()->bound('current_store') ? app('current_store') : null;
    $storeName = $store?->name ?? config('app.name');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Page Not Found - {{ $storeName }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-white text-gray-700 antialiased dark:bg-gray-950 dark:text-gray-300">
        {{-- Minimal Header --}}
        <header class="border-b border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-950">
            <div class="mx-auto flex max-w-7xl items-center justify-center px-4 py-4 sm:px-6 lg:px-8">
                <a href="/" class="text-xl font-bold text-gray-900 dark:text-white lg:text-2xl">
                    {{ $storeName }}
                </a>
            </div>
        </header>

        {{-- Content --}}
        <main class="flex min-h-[60vh] items-center justify-center px-4">
            <div class="text-center">
                <p class="text-7xl font-bold text-blue-600 sm:text-9xl">404</p>
                <h1 class="mt-4 text-2xl font-bold text-gray-900 dark:text-white sm:text-3xl">
                    Page not found
                </h1>
                <p class="mt-4 text-base text-gray-600 dark:text-gray-400">
                    Sorry, we could not find the page you are looking for.
                </p>
                <div class="mt-8">
                    <a href="/"
                       class="inline-flex items-center rounded-md bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-blue-500">
                        Go back home
                    </a>
                </div>
            </div>
        </main>

        {{-- Footer --}}
        <footer class="border-t border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-900">
            <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <p class="text-center text-sm text-gray-400">
                    &copy; {{ date('Y') }} {{ $storeName }}. All rights reserved.
                </p>
            </div>
        </footer>
    </body>
</html>
