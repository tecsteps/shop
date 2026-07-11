<div class="flex min-h-svh items-center justify-center bg-zinc-50 p-4 dark:bg-zinc-950">
    <main class="w-full max-w-md rounded-2xl border border-zinc-200 bg-white p-6 shadow-xl dark:border-zinc-800 dark:bg-zinc-900 sm:p-8">
        <div class="mb-8 space-y-2 text-center"><div class="mx-auto flex size-12 items-center justify-center rounded-xl bg-zinc-950 text-white dark:bg-white dark:text-zinc-950" aria-hidden="true"><flux:icon.shopping-bag /></div><flux:heading size="xl" level="1">Sign in</flux:heading><flux:text>Access your store administration.</flux:text></div>
        <form wire:submit="login" class="space-y-5">
            <flux:field><flux:label>Email</flux:label><flux:input wire:model="email" type="email" autocomplete="username" required autofocus /><flux:error name="email" /></flux:field>
            <flux:field><flux:label>Password</flux:label><flux:input wire:model="password" type="password" autocomplete="current-password" required viewable /><flux:error name="password" /></flux:field>
            <flux:checkbox wire:model="remember" label="Remember me" />
            <flux:button type="submit" variant="primary" class="w-full" wire:loading.attr="disabled"><span wire:loading.remove wire:target="login">Sign in</span><span wire:loading wire:target="login">Signing in…</span></flux:button>
        </form>
    </main>
</div>
