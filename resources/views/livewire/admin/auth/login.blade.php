<div class="mx-auto w-full max-w-md">
    <div class="mb-8 text-center">
        <flux:heading size="xl">Sign in to the admin</flux:heading>
        <flux:subheading>Manage products, orders, and customers.</flux:subheading>
    </div>

    <form wire:submit="login" class="flex flex-col gap-4">
        <flux:input
            type="email"
            label="Email"
            wire:model="email"
            placeholder="you@store.com"
            autocomplete="email"
            autofocus
            required
        />

        <flux:input
            type="password"
            label="Password"
            wire:model="password"
            placeholder="Your password"
            autocomplete="current-password"
            required
            viewable
        />

        <div class="flex items-center justify-between">
            <flux:checkbox label="Remember me" wire:model="remember" />
        </div>

        <flux:button type="submit" variant="primary" class="w-full">
            Sign in
        </flux:button>
    </form>

    <div class="mt-6 text-center text-sm text-zinc-500">
        <p>Demo: <strong>owner@demo.test</strong> / <strong>password</strong></p>
    </div>
</div>
