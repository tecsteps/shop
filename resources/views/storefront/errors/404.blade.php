<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page not found</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-white antialiased dark:bg-zinc-900">
    <div class="relative px-4 text-center">
        <span class="absolute inset-0 flex items-center justify-center text-[12rem] font-bold text-zinc-100 select-none dark:text-zinc-800">404</span>
        <div class="relative z-10">
            <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">Page not found</h1>
            <p class="mt-2 text-zinc-600 dark:text-zinc-400">The page you are looking for does not exist or has been moved.</p>
            <form action="/search" method="GET" class="mt-6">
                <input type="text" name="q" placeholder="Search products..."
                       class="w-full max-w-sm rounded-md border-zinc-300 text-sm focus:border-zinc-500 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
            </form>
            <a href="/" class="mt-4 inline-block rounded-md bg-zinc-900 px-6 py-3 text-sm font-semibold text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                Go to home page
            </a>
        </div>
    </div>
</body>
</html>
