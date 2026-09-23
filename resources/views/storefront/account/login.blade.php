
@section('title', 'Log in')
<div>
<section class="mx-auto max-w-xl px-4 py-12 sm:px-6 sm:py-16">
        <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm sm:p-9 dark:border-stone-800 dark:bg-stone-950">
            <p class="text-center text-xs font-semibold uppercase tracking-[0.18em] text-blue-800 dark:text-blue-300">Welcome back</p><h1 class="mt-2 text-center text-2xl font-semibold tracking-tight sm:text-3xl">Log in to your account</h1><p class="mt-2 text-center text-sm text-stone-600 dark:text-stone-300">See your orders, addresses, and saved details.</p>
            @if ($errors->has('credentials'))<div role="alert" class="mt-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900 dark:border-rose-900 dark:bg-rose-950/50 dark:text-rose-100">Invalid credentials</div>@endif
            <form wire:submit="login" class="mt-7 grid gap-5">
                <div><label for="account-email" class="mb-1.5 block text-sm font-medium">Email address <span class="text-rose-700" aria-hidden="true">*</span></label><input id="account-email" name="email" type="email" wire:model.blur="email" autocomplete="email" required aria-describedby="account-email-error" class="storefront-input">@error('email')<p id="account-email-error" class="mt-1.5 text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p>@enderror</div>
                <div><div class="flex items-center justify-between gap-3"><label for="account-password" class="mb-1.5 block text-sm font-medium">Password <span class="text-rose-700" aria-hidden="true">*</span></label><a href="{{ route('storefront.password.request') }}" class="mb-1.5 text-xs font-medium text-blue-800 underline underline-offset-4 dark:text-blue-300">Forgot password?</a></div><input id="account-password" name="password" type="password" wire:model="password" autocomplete="current-password" required aria-describedby="account-password-error" class="storefront-input">@error('password')<p id="account-password-error" class="mt-1.5 text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p>@enderror</div>
                <button type="submit" wire:loading.attr="disabled" class="inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-stone-950 px-6 text-sm font-semibold text-white hover:bg-blue-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700 disabled:opacity-60 dark:bg-white dark:text-stone-950 dark:hover:bg-blue-200"><span wire:loading.remove wire:target="login">Log in</span><span wire:loading wire:target="login">Signing in…</span></button>
            </form>
            <p class="mt-6 text-center text-sm text-stone-600 dark:text-stone-300">Don’t have an account? <a href="/account/register" class="font-semibold text-blue-800 underline underline-offset-4 dark:text-blue-300">Create one</a></p>
        </div>
    </section>
</div>
