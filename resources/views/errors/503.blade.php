<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Maintenance</title>
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen items-center justify-center bg-zinc-50 p-4 dark:bg-zinc-950">
    <div class="text-center">
        <p class="text-3xl font-bold text-zinc-900 dark:text-white">{{ app('current_store')->name ?? 'Store' }}</p>
        <h1 class="mt-6 text-2xl font-bold text-zinc-900 dark:text-white">We will be back soon</h1>
        <p class="mt-3 text-zinc-500 dark:text-zinc-400">We are currently performing maintenance. Please check back shortly.</p>
    </div>
</body>
</html>
