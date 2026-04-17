<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen items-center justify-center bg-white px-4 dark:bg-gray-950">
    <div class="text-center">
        <p class="text-8xl font-bold text-gray-100 dark:text-gray-800">404</p>
        <h1 class="-mt-4 text-2xl font-bold text-gray-900 dark:text-white">Page not found</h1>
        <p class="mt-2 max-w-sm text-gray-500 dark:text-gray-400">
            The page you're looking for doesn't exist or has been moved.
        </p>
        <div class="mt-8 flex flex-col items-center gap-4">
            <form action="/search" method="GET" class="flex w-full max-w-sm gap-2">
                <input type="search"
                       name="q"
                       placeholder="Search..."
                       class="flex-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                <button type="submit"
                        class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                    Search
                </button>
            </form>
            <a href="/" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
                Go to home page
            </a>
        </div>
    </div>
</body>
</html>
