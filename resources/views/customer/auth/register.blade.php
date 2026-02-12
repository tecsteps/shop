@extends('storefront.layout')

@section('content')
<section class="mx-auto max-w-md rounded-2xl border border-zinc-200 bg-white p-6 sm:p-8">
    <header class="text-center">
        <h1 class="text-2xl font-bold tracking-tight text-zinc-900">Create an account</h1>
        <p class="mt-2 text-sm text-zinc-600">Save addresses, review orders, and get a faster checkout experience.</p>
    </header>

    <form method="POST" action="{{ route('storefront.account.register.submit') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <label for="name" class="block text-sm font-medium text-zinc-700">Full name</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autocomplete="name" class="mt-1 w-full rounded-md border-zinc-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-zinc-700">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" class="mt-1 w-full rounded-md border-zinc-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-zinc-700">Password</label>
            <input id="password" type="password" name="password" required autocomplete="new-password" class="mt-1 w-full rounded-md border-zinc-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-zinc-700">Confirm password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="mt-1 w-full rounded-md border-zinc-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
        </div>

        <label class="flex items-center gap-2 text-sm text-zinc-600">
            <input type="checkbox" name="marketing_opt_in" value="1" class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500" @checked(old('marketing_opt_in'))>
            Subscribe to marketing emails
        </label>

        <button type="submit" class="w-full rounded-md bg-zinc-900 px-4 py-3 text-sm font-semibold text-white hover:bg-zinc-700">Create account</button>
    </form>

    <p class="mt-6 text-center text-sm text-zinc-600">
        Already have an account?
        <a href="{{ route('storefront.account.login') }}" class="font-semibold text-zinc-900 hover:underline">Log in</a>
    </p>
</section>
@endsection
