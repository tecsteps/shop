<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Store unavailable</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-white text-zinc-950 antialiased dark:bg-zinc-950 dark:text-white">
    <main class="flex min-h-screen items-center justify-center px-6 py-16">
        <div class="w-full max-w-md text-center">
            <p class="text-sm font-semibold uppercase tracking-normal text-zinc-500 dark:text-zinc-400">503</p>
            <h1 class="mt-3 text-3xl font-semibold tracking-normal">Store unavailable</h1>
            <p class="mt-4 text-sm leading-6 text-zinc-600 dark:text-zinc-400">This storefront is temporarily unavailable.</p>
        </div>
    </main>
</body>
</html>
