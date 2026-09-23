<div class="mx-auto w-full max-w-md px-5 py-8">
    <a href="{{ route('admin.login') }}" class="mb-8 block text-center text-lg font-semibold">{{ config('app.name', 'Shop') }} <span class="font-normal text-zinc-500">Admin</span></a>
    <form wire:submit="sendResetLink" class="grid gap-5 rounded-3xl border border-zinc-200 bg-white p-6 shadow-xl sm:p-8 dark:border-zinc-800 dark:bg-zinc-900">
        <div><h1 class="text-2xl font-semibold tracking-tight">Reset your password</h1><p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Enter your staff email address and we’ll send reset instructions if an account exists.</p></div>
        @if ($statusMessage)<p role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-100">{{ $statusMessage }}</p>@endif
        <div><label for="admin-reset-email" class="mb-1.5 block text-sm font-medium">Email address</label><input id="admin-reset-email" type="email" wire:model.blur="email" autocomplete="email" required class="min-h-12 w-full rounded-xl border border-zinc-300 bg-white px-3.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-950">@error('email')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror</div>
        <button type="submit" wire:loading.attr="disabled" class="inline-flex min-h-12 items-center justify-center rounded-xl bg-blue-600 px-5 text-sm font-semibold text-white hover:bg-blue-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:opacity-60">Send reset link</button>
        <a href="{{ route('admin.login') }}" class="text-center text-sm font-medium text-blue-700 underline underline-offset-4 dark:text-blue-300">Back to sign in</a>
    </form>
</div>
