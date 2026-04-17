<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Service Unavailable</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="flex min-h-screen items-center justify-center bg-white antialiased dark:bg-gray-950">
        <div class="px-4 text-center">
            <p class="text-7xl font-bold text-gray-400 sm:text-9xl">503</p>
            <h1 class="mt-4 text-2xl font-bold text-gray-900 dark:text-white sm:text-3xl">
                Service Unavailable
            </h1>
            <p class="mt-4 text-base text-gray-600 dark:text-gray-400">
                We are currently performing maintenance. Please check back shortly.
            </p>
        </div>
    </body>
</html>
