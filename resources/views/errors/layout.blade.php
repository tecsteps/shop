@php
    /**
     * Shared layout for storefront-family error pages (spec 04 §13).
     *
     * The store context is not guaranteed here: 404s fire for unknown
     * domains and 503s for suspended stores before the tenant middleware
     * shares `currentStore`, and 500s may be caused by the database being
     * down. Branding is therefore resolved defensively from the hostname
     * and every failure falls back to the platform name.
     */
    $errorStore = null;

    try {
        $errorDomain = \App\Models\StoreDomain::query()
            ->where('hostname', request()->getHost())
            ->first();
        $errorStore = $errorDomain?->store;
    } catch (\Throwable) {
        $errorStore = null;
    }

    $brandName = $errorStore?->name ?? config('app.name');
    $errorTitle = $title ?? 'Error';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <title>{{ $errorTitle }} - {{ $brandName }}</title>
    <meta name="robots" content="noindex">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        // Same system/toggle dark-mode behavior as the storefront layout.
        (function () {
            const stored = localStorage.getItem('theme');
            const dark = stored === 'dark'
                || (stored === null && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', dark);
        })();
    </script>
</head>
<body class="flex min-h-screen flex-col bg-white text-gray-900 antialiased dark:bg-gray-950 dark:text-gray-100">
    <a href="#main-content"
       class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-50 focus:rounded-md focus:bg-blue-600 focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white focus:shadow-lg focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-950">
        Skip to main content
    </a>

    <header class="border-b border-gray-200 dark:border-gray-800">
        <div class="mx-auto flex h-16 max-w-7xl items-center px-4 sm:px-6 lg:px-8">
            <a href="/" class="text-lg font-bold tracking-tight text-gray-900 focus:outline-hidden focus:ring-2 focus:ring-blue-500 rounded dark:text-white">
                {{ $brandName }}
            </a>
        </div>
    </header>

    <main id="main-content" class="flex flex-1 items-center justify-center px-4 py-16 sm:px-6 lg:px-8">
        @yield('content')
    </main>

    <footer class="border-t border-gray-200 dark:border-gray-800">
        <nav class="mx-auto flex max-w-7xl flex-wrap items-center justify-center gap-x-8 gap-y-2 px-4 py-8 sm:px-6 lg:px-8" aria-label="Helpful links">
            <a href="/" class="text-sm font-medium text-gray-600 hover:text-gray-900 focus:outline-hidden focus:ring-2 focus:ring-blue-500 rounded dark:text-gray-400 dark:hover:text-white">Home</a>
            <a href="/collections" class="text-sm font-medium text-gray-600 hover:text-gray-900 focus:outline-hidden focus:ring-2 focus:ring-blue-500 rounded dark:text-gray-400 dark:hover:text-white">Collections</a>
            <a href="/search" class="text-sm font-medium text-gray-600 hover:text-gray-900 focus:outline-hidden focus:ring-2 focus:ring-blue-500 rounded dark:text-gray-400 dark:hover:text-white">Search</a>
        </nav>
    </footer>
</body>
</html>
