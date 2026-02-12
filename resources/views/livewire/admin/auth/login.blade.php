<div class="flex flex-col gap-6">
    <div class="text-center">
        <flux:heading size="lg">Sign in to Admin</flux:heading>
        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Enter your email and password below</p>
    </div>

    <form wire:submit="login" class="flex flex-col gap-6">
        <flux:input
            wire:model="email"
            label="Email address"
            type="email"
            required
            autofocus
            autocomplete="email"
            placeholder="email@example.com"
        />

        <flux:input
            wire:model="password"
            label="Password"
            type="password"
            required
            autocomplete="current-password"
            placeholder="Password"
            viewable
        />

        <flux:checkbox wire:model="remember" label="Remember me" />

        <flux:button variant="primary" type="submit" class="w-full">
            Sign in
        </flux:button>
    </form>
</div>
