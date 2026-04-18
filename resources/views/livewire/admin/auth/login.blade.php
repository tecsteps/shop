<div class="flex min-h-screen items-center justify-center bg-zinc-50 p-6 dark:bg-zinc-950">
    <div class="w-full max-w-md rounded-2xl bg-white p-8 shadow-sm dark:bg-zinc-900">
        <flux:heading size="xl">Admin Login</flux:heading>
        <flux:text class="mt-1">Sign in to manage your store</flux:text>

        <form wire:submit="login" class="mt-6 space-y-4">
            <flux:field>
                <flux:label>Email</flux:label>
                <flux:input wire:model="email" type="email" required autofocus autocomplete="email" />
                <flux:error name="email" />
            </flux:field>

            <flux:field>
                <flux:label>Password</flux:label>
                <flux:input wire:model="password" type="password" required autocomplete="current-password" />
                <flux:error name="password" />
            </flux:field>

            <flux:checkbox wire:model="remember" label="Remember me" />

            <flux:button type="submit" variant="primary" class="w-full">
                <span wire:loading.remove>Sign in</span>
                <span wire:loading>Signing in...</span>
            </flux:button>
        </form>
    </div>
</div>
