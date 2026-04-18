<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Page not found</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
</head>
<body class="flex min-h-full items-center justify-center bg-white font-sans text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
    <div class="mx-auto max-w-xl px-6 py-24 text-center">
        <p class="text-sm font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">404</p>
        <h1 class="mt-2 text-3xl font-bold">Page not found</h1>
        <p class="mt-4 text-zinc-600 dark:text-zinc-400">
            The page you are looking for does not exist or has been moved.
        </p>
        <div class="mt-8">
            <a href="/" class="inline-flex items-center rounded-md border border-zinc-200 bg-white px-4 py-2 text-sm font-medium hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:bg-zinc-800">
                Back to home
            </a>
        </div>
    </div>
    @fluxScripts
</body>
</html>
