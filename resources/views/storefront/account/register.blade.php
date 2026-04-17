@extends('storefront.layout')

@section('title', 'Create Account')

@section('content')
<div class="mx-auto w-full max-w-md rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <h1 class="text-2xl font-semibold text-slate-900">Create account</h1>

    <form class="mt-6 space-y-4" method="POST" action="{{ route('account.register.store') }}">
        @csrf
        <div>
            <label class="block text-sm font-medium text-slate-700" for="name">Full name</label>
            <input class="mt-1 w-full rounded border border-slate-300 px-3 py-2" id="name" name="name" type="text" value="{{ old('name') }}" required autofocus>
            @error('name')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700" for="email">Email</label>
            <input class="mt-1 w-full rounded border border-slate-300 px-3 py-2" id="email" name="email" type="email" value="{{ old('email') }}" required>
            @error('email')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700" for="password">Password</label>
            <input class="mt-1 w-full rounded border border-slate-300 px-3 py-2" id="password" name="password" type="password" required>
            @error('password')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700" for="password_confirmation">Confirm password</label>
            <input class="mt-1 w-full rounded border border-slate-300 px-3 py-2" id="password_confirmation" name="password_confirmation" type="password" required>
        </div>
        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="marketing_opt_in" value="1" class="rounded border-slate-300" {{ old('marketing_opt_in') ? 'checked' : '' }}> Receive product updates
        </label>
        <button class="w-full rounded bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700" type="submit">Create account</button>
    </form>

    <p class="mt-4 text-sm text-slate-600">
        Already have an account?
        <a class="text-slate-800 hover:text-slate-900" href="{{ route('account.login') }}">Log in</a>
    </p>
</div>
@endsection
