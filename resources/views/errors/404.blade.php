<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Page Not Found</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-white text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">
    <div class="flex min-h-screen flex-col items-center justify-center px-4">
        <p class="text-sm font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">404</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight">Page not found</h1>
        <p class="mt-4 text-zinc-600 dark:text-zinc-400">
            Sorry, the page you are looking for does not exist or has been moved.
        </p>
        <a href="/" class="mt-8 inline-flex items-center rounded-lg bg-zinc-900 px-5 py-2.5 text-sm font-medium text-white transition-colors hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200">
            Back to Home
        </a>
    </div>
</body>
</html>
