@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <title>{{ $title ?? (app()->bound('current_store') ? app('current_store')->name : config('app.name')) }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
</head>
<body class="min-h-full bg-white font-sans text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:left-2 focus:top-2 focus:z-50 focus:rounded focus:bg-white focus:p-2 focus:shadow">Skip to content</a>

    <x-storefront.announcement-bar />
    <x-storefront.header />

    <main id="main-content">
        {{ $slot }}
    </main>

    <x-storefront.footer />
    <livewire:storefront.cart-drawer />

    @fluxScripts
</body>
</html>
