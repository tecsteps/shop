<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Service Unavailable</title>
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen flex-col items-center justify-center bg-white px-4 text-gray-700 antialiased dark:bg-gray-950 dark:text-gray-300">
    <div class="text-center">
        <p class="text-7xl font-bold text-gray-900 dark:text-white">503</p>
        <h1 class="mt-4 text-2xl font-semibold text-gray-900 dark:text-white">Service unavailable</h1>
        <p class="mt-2 text-base text-gray-600 dark:text-gray-400">
            We are currently performing maintenance. Please check back shortly.
        </p>
        <div class="mt-8">
            <button onclick="location.reload()"
                    class="inline-flex items-center rounded-md bg-gray-900 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition-colors hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100">
                Try again
            </button>
        </div>
    </div>
</body>
</html>
