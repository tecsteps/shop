<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-100 px-4">
<div class="w-full max-w-md rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <h1 class="text-2xl font-semibold text-slate-900">Sign in</h1>
    <p class="mt-1 text-sm text-slate-600">Access your admin workspace.</p>

    <form class="mt-6 space-y-4" method="POST" action="{{ route('admin.auth.login.store') }}">
        @csrf
        <div>
            <label class="block text-sm font-medium text-slate-700" for="email">Email</label>
            <input class="mt-1 w-full rounded border border-slate-300 px-3 py-2" id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
            @error('email')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700" for="password">Password</label>
            <input class="mt-1 w-full rounded border border-slate-300 px-3 py-2" id="password" name="password" type="password" required>
            @error('password')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="remember" value="1" class="rounded border-slate-300"> Remember me
        </label>
        <button class="w-full rounded bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700" type="submit">Sign in</button>
    </form>

    <div class="mt-4 text-sm">
        <a class="text-slate-700 hover:text-slate-900" href="{{ route('admin.auth.forgot-password') }}">Forgot your password?</a>
    </div>
</div>
</body>
</html>
