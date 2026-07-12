@props([
    'title' => null,
    'metaDescription' => '',
    'darkMode' => 'system',
])

@php
    $appName = (string) config('app.name', 'Shop');
    $pageTitle = filled($title) ? (string) $title : $appName;
    $documentTitle = $pageTitle === $appName ? $appName : $pageTitle.' — '.$appName;
    $darkMode = in_array($darkMode, ['system', 'toggle', 'light', 'dark'], true) ? $darkMode : 'system';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ $darkMode === 'dark' ? 'dark' : '' }}" data-theme-mode="{{ $darkMode }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="{{ $metaDescription }}">

        <title>{{ $documentTitle }}</title>

        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">

        <script>
            (() => {
                const configuredMode = @js($darkMode);
                let selectedMode = configuredMode;

                if (configuredMode === 'toggle') {
                    try {
                        selectedMode = localStorage.getItem('storefront-theme') || 'system';
                    } catch (error) {
                        selectedMode = 'system';
                    }
                }

                const colorScheme = window.matchMedia('(prefers-color-scheme: dark)');
                const applyTheme = (isDark) => {
                    document.documentElement.classList.toggle('dark', isDark);
                    document.documentElement.style.colorScheme = isDark ? 'dark' : 'light';
                };

                applyTheme(selectedMode === 'dark' || (selectedMode === 'system' && colorScheme.matches));

                if (selectedMode === 'system' && ! window.__storefrontColorSchemeListener) {
                    window.__storefrontColorSchemeListener = (event) => applyTheme(event.matches);
                    colorScheme.addEventListener('change', window.__storefrontColorSchemeListener, { passive: true });
                }
            })();
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
        @stack('head')
    </head>
    <body {{ $attributes->class('storefront-shell min-h-screen bg-white text-zinc-700 antialiased dark:bg-zinc-950 dark:text-zinc-300') }}>
        <a href="#main-content" class="storefront-skip-link">{{ __('Skip to main content') }}</a>

        @isset($announcement)
            {{ $announcement }}
        @endisset

        @isset($header)
            {{ $header }}
        @endisset

        <main id="main-content" class="min-h-[70vh]" tabindex="-1">
            {{ $slot }}
        </main>

        @isset($footer)
            {{ $footer }}
        @endisset

        @isset($cart)
            {{ $cart }}
        @endisset

        @isset($search)
            {{ $search }}
        @endisset

        @livewireScripts
        @stack('scripts')
    </body>
</html>
