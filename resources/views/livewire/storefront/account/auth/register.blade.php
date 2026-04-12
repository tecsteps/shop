<div class="mx-auto max-w-md space-y-8">
    <header class="space-y-2 text-center">
        <h1 class="text-3xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-50">Create account</h1>
        <p class="text-sm text-zinc-600 dark:text-zinc-400">Sign up to track orders and check out faster.</p>
    </header>

    <form wire:submit.prevent="register" class="space-y-5 rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
        <label class="block text-sm">
            <span class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">Name</span>
            <input type="text" wire:model="name" autocomplete="name" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
            @error('name') <span class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
        </label>

        <label class="block text-sm">
            <span class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">Email</span>
            <input type="email" wire:model="email" autocomplete="email" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
            @error('email') <span class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
        </label>

        <label class="block text-sm">
            <span class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">Password</span>
            <input type="password" wire:model="password" autocomplete="new-password" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
            @error('password') <span class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
        </label>

        <label class="block text-sm">
            <span class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">Confirm password</span>
            <input type="password" wire:model="password_confirmation" autocomplete="new-password" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
        </label>

        <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
            <input type="checkbox" wire:model="marketing_opt_in" class="h-4 w-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900 dark:border-zinc-700" />
            <span>Send me product news and offers</span>
        </label>

        <button type="submit" class="inline-flex w-full items-center justify-center rounded-full bg-zinc-900 px-6 py-3 text-sm font-semibold text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
            Create account
        </button>
    </form>

    <p class="text-center text-sm text-zinc-600 dark:text-zinc-400">
        Already have an account?
        <a href="{{ route('storefront.account.login') }}" class="font-semibold text-zinc-900 underline-offset-2 hover:underline dark:text-zinc-100">Sign in</a>
    </p>
</div>
