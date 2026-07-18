<div class="mx-auto max-w-md px-4 py-16 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Log in</h1>
    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
        Don't have an account?
        <a href="{{ route('storefront.account.register') }}" wire:navigate class="text-blue-600 hover:underline dark:text-blue-400">Create one</a>
    </p>

    <form wire:submit.prevent="login" class="mt-8 space-y-4">
        <flux:field>
            <flux:label for="email">Email</flux:label>
            <flux:input id="email" type="email" wire:model="email" autofocus required />
            <flux:error name="email" />
        </flux:field>

        <flux:field>
            <flux:label for="password">Password</flux:label>
            <flux:input id="password" type="password" wire:model="password" required />
            <flux:error name="password" />
        </flux:field>

        <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
            <input type="checkbox" wire:model="remember" class="rounded border-zinc-300 text-blue-600 dark:border-zinc-700" />
            Remember me
        </label>

        <flux:button type="submit" variant="primary" class="w-full" wire:loading.attr="disabled" wire:target="login">
            <span wire:loading.remove wire:target="login">Log in</span>
            <span wire:loading wire:target="login">Logging in...</span>
        </flux:button>
    </form>
</div>
