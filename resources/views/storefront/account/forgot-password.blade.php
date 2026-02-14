@extends('storefront.layout')

@section('title', 'Forgot Password')

@section('content')
<div class="mx-auto w-full max-w-md rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <h1 class="text-2xl font-semibold text-slate-900">Forgot password</h1>
    <p class="mt-2 text-sm text-slate-600">Enter your account email and we will send you a password reset link.</p>

    <form class="mt-6 space-y-4" method="POST" action="{{ route('account.forgot-password.store') }}">
        @csrf
        <div>
            <label class="block text-sm font-medium text-slate-700" for="email">Email</label>
            <input class="mt-1 w-full rounded border border-slate-300 px-3 py-2" id="email" name="email" type="email" value="{{ old('email') }}" required>
            @error('email')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
        <button class="w-full rounded bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700" type="submit">Send reset link</button>
    </form>
</div>
@endsection
