<div class="mx-auto max-w-md space-y-8">
    <header class="space-y-2 text-center">
        <h1 class="text-3xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-50">Sign in</h1>
        <p class="text-sm text-zinc-600 dark:text-zinc-400">Access your orders and saved addresses.</p>
    </header>

    <form wire:submit.prevent="login" class="space-y-5 rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
        <label class="block text-sm">
            <span class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">Email</span>
            <input type="email" wire:model="email" autocomplete="email" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
            @error('email') <span class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
        </label>

        <label class="block text-sm">
            <span class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">Password</span>
            <input type="password" wire:model="password" autocomplete="current-password" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
            @error('password') <span class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
        </label>

        <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
            <input type="checkbox" wire:model="remember" class="h-4 w-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900 dark:border-zinc-700" />
            <span>Remember me</span>
        </label>

        <button type="submit" class="inline-flex w-full items-center justify-center rounded-full bg-zinc-900 px-6 py-3 text-sm font-semibold text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
            Sign in
        </button>
    </form>

    <p class="text-center text-sm text-zinc-600 dark:text-zinc-400">
        New customer?
        <a href="{{ route('storefront.account.register') }}" class="font-semibold text-zinc-900 underline-offset-2 hover:underline dark:text-zinc-100">Create an account</a>
    </p>
</div>
