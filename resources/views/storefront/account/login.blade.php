@extends('storefront.layout')

@section('title', 'Account Login')

@section('content')
<div class="mx-auto w-full max-w-md rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <h1 class="text-2xl font-semibold text-slate-900">Log in</h1>

    <form class="mt-6 space-y-4" method="POST" action="{{ route('account.login.store') }}">
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
        <button class="w-full rounded bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700" type="submit">Log in</button>
    </form>

    <div class="mt-4 flex justify-between text-sm">
        <a class="text-slate-700 hover:text-slate-900" href="{{ route('account.register') }}">Create account</a>
        <a class="text-slate-700 hover:text-slate-900" href="{{ route('account.forgot-password') }}">Forgot password?</a>
    </div>
</div>
@endsection
