@extends('storefront.layout')

@section('content')
<section class="mx-auto max-w-md rounded-2xl border border-zinc-200 bg-white p-6 sm:p-8">
    <header class="text-center">
        <h1 class="text-2xl font-bold tracking-tight text-zinc-900">Log in to your account</h1>
        <p class="mt-2 text-sm text-zinc-600">Access order history, checkout faster, and manage your addresses.</p>
    </header>

    <form method="POST" action="{{ route('storefront.account.login.submit') }}" class="mt-6 space-y-4">
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

    <p class="mt-6 text-center text-sm text-zinc-600">
        Don't have an account?
        <a href="{{ route('storefront.account.register') }}" class="font-semibold text-zinc-900 hover:underline">Create one</a>
    </p>
</section>
@endsection
