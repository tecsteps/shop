<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => 'Admin Login'])
    </head>
    <body class="min-h-screen bg-zinc-100 text-zinc-900">
        <main class="mx-auto flex min-h-screen w-full max-w-md items-center px-4 py-10 sm:px-6">
            <section class="w-full rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm sm:p-8">
                <header>
                    <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Admin</p>
                    <h1 class="mt-1 text-2xl font-semibold tracking-tight text-zinc-900">Log in</h1>
                    <p class="mt-2 text-sm text-zinc-600">Sign in to manage your store, orders, and catalog.</p>
                </header>

                <form method="POST" action="{{ route('admin.login.submit') }}" class="mt-6 space-y-4">
                    @csrf

                    <div>
                        <label for="email" class="block text-sm font-medium text-zinc-700">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" class="mt-1 w-full rounded-md border-zinc-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-zinc-700">Password</label>
                        <input id="password" type="password" name="password" required autocomplete="current-password" class="mt-1 w-full rounded-md border-zinc-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    </div>

                    <label class="flex items-center gap-2 text-sm text-zinc-600">
                        <input type="checkbox" name="remember" value="1" class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500" @checked(old('remember'))>
                        Keep me signed in
                    </label>

                    <button type="submit" class="w-full rounded-md bg-zinc-900 px-4 py-3 text-sm font-semibold text-white hover:bg-zinc-700">Log in</button>
                </form>
            </section>
        </main>
    </body>
</html>

