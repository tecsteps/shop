<div class="flex min-h-screen items-center justify-center">
    <div class="w-full max-w-md space-y-6 p-6">
        <div class="text-center">
            <flux:heading size="xl">Admin Login</flux:heading>
            <flux:text class="mt-2">Sign in to your admin account</flux:text>
        </div>

        <form wire:submit="login" class="space-y-4">
            <flux:input
                wire:model="email"
                label="Email"
                type="email"
                placeholder="admin@example.com"
                required
            />

            <flux:input
                wire:model="password"
                label="Password"
                type="password"
                required
            />

            <div class="flex items-center justify-between">
                <flux:checkbox wire:model="remember" label="Remember me" />
            </div>

            <flux:button type="submit" variant="primary" class="w-full">
                Sign In
            </flux:button>
        </form>
    </div>
</div>
