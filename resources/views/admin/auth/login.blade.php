<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin login</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="grid min-h-screen place-items-center bg-zinc-100 px-4 font-sans dark:bg-zinc-950">
    <form method="POST" action="{{ route('admin.authenticate') }}" class="w-full max-w-md rounded-lg border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        @csrf
        <h1 class="text-2xl font-bold tracking-normal">Admin login</h1>
        @if($errors->any())
            <div class="mt-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">{{ $errors->first() }}</div>
        @endif
        <div class="mt-6 grid gap-4">
            <label class="grid gap-2"><span>Email</span><input name="email" type="email" value="{{ old('email') }}" class="rounded-md border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950"></label>
            <label class="grid gap-2"><span>Password</span><input name="password" type="password" class="rounded-md border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950"></label>
            <button class="rounded-md bg-zinc-950 px-4 py-3 font-medium text-white dark:bg-white dark:text-zinc-950">Log in</button>
        </div>
    </form>
</body>
</html>

