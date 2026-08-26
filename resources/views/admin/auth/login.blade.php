<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login</title>
    @vite(['resources/css/app.css'])
</head>
<body class="h-full bg-gray-50 dark:bg-zinc-950">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="w-full max-w-sm rounded-xl border border-zinc-200 bg-white p-8 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <h1 class="mb-6 text-xl font-semibold">Admin Login</h1>
            <form method="POST" action="{{ route('admin.login') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="email" class="mb-1 block text-sm font-medium">Email</label>
                    <input type="email" name="email" id="email" required autofocus class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800" />
                </div>
                <div>
                    <label for="password" class="mb-1 block text-sm font-medium">Password</label>
                    <input type="password" name="password" id="password" required class="w-full rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800" />
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="remember" value="1" /> Remember me
                </label>
                @error('email')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                <button type="submit" class="w-full rounded-lg bg-zinc-900 px-4 py-2 text-white dark:bg-white dark:text-zinc-900">Login</button>
            </form>
        </div>
    </div>
</body>
</html>
