<div class="mx-auto max-w-md px-4 py-16 sm:px-6 lg:px-8">
    <h1 class="text-center text-3xl font-semibold tracking-normal">Reset password</h1>

    <form wire:submit="sendResetLink" class="mt-8 flex flex-col gap-4 rounded-lg border border-zinc-200 p-6 dark:border-zinc-800">
        @if ($resetLinkSent && session('status'))
            <p class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200">
                {{ session('status') }}
            </p>
        @endif

        <div>
            <label for="customer-reset-email" class="text-sm font-medium">Email</label>
            <input id="customer-reset-email" wire:model="email" type="email" autocomplete="email" class="mt-2 w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900">
            @error('email') <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="rounded-md bg-zinc-950 px-4 py-3 text-sm font-semibold text-white dark:bg-white dark:text-zinc-950">
            Email reset link
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-zinc-600 dark:text-zinc-400">
        Remembered it?
        <a href="{{ route('storefront.account.login') }}" class="font-semibold text-zinc-950 underline underline-offset-4 dark:text-white">Log in</a>
    </p>
</div>
