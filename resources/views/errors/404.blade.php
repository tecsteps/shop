<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Page not found</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-white text-zinc-950 antialiased dark:bg-zinc-950 dark:text-white">
    <main class="flex min-h-screen items-center justify-center px-6 py-16">
        <div class="w-full max-w-md text-center">
            <p class="text-sm font-semibold uppercase tracking-normal text-zinc-500 dark:text-zinc-400">404</p>
            <h1 class="mt-3 text-3xl font-semibold tracking-normal">Page not found</h1>
            <p class="mt-4 text-sm leading-6 text-zinc-600 dark:text-zinc-400">The page you requested is not available for this store.</p>
            <a href="{{ route('home') }}" class="mt-8 inline-flex rounded-md bg-zinc-950 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200">Return home</a>
        </div>
    </main>
</body>
</html>
