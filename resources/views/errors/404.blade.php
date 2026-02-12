<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Page Not Found</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        if (localStorage.getItem('darkMode') === 'true' || (!('darkMode' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="min-h-screen flex items-center justify-center bg-white text-gray-700 dark:bg-gray-950 dark:text-gray-300 antialiased">
    <div class="text-center px-4">
        <p class="text-6xl font-bold text-gray-300 dark:text-gray-700">404</p>
        <h1 class="mt-4 text-2xl font-semibold text-gray-900 dark:text-white">Page not found</h1>
        <p class="mt-2 text-gray-500 dark:text-gray-400">Sorry, we could not find the page you are looking for.</p>
        <div class="mt-8 flex items-center justify-center gap-4">
            <a href="/" class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-medium text-white hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100 transition-colors">
                Go home
            </a>
            <a href="javascript:history.back()" class="text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors">
                Go back
            </a>
        </div>
    </div>
</body>
</html>
