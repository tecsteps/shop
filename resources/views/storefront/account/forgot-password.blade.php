@section('title', 'Reset password')
<div>
    <section class="mx-auto max-w-xl px-4 py-12 sm:px-6 sm:py-16">
        <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm sm:p-9 dark:border-stone-800 dark:bg-stone-950">
            <p class="text-center text-xs font-semibold uppercase tracking-[0.18em] text-blue-800 dark:text-blue-300">Account recovery</p><h1 class="mt-2 text-center text-2xl font-semibold tracking-tight sm:text-3xl">Reset your password</h1><p class="mt-2 text-center text-sm text-stone-600 dark:text-stone-300">Enter your account email. We’ll send a link if an account exists.</p>
            @if ($statusMessage)<p role="status" class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-100">{{ $statusMessage }}</p>@endif
            <form wire:submit="sendResetLink" class="mt-7 grid gap-5"><div><label for="reset-email" class="mb-1.5 block text-sm font-medium">Email address</label><input id="reset-email" type="email" wire:model.blur="email" autocomplete="email" required class="storefront-input">@error('email')<p class="mt-1.5 text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p>@enderror</div><button type="submit" wire:loading.attr="disabled" class="inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-stone-950 px-6 text-sm font-semibold text-white hover:bg-blue-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700 disabled:opacity-60 dark:bg-white dark:text-stone-950 dark:hover:bg-blue-200">Send reset link</button></form>
            <p class="mt-6 text-center text-sm"><a href="{{ route('storefront.login') }}" class="font-semibold text-blue-800 underline underline-offset-4 dark:text-blue-300">Back to sign in</a></p>
        </div>
    </section>
</div>
