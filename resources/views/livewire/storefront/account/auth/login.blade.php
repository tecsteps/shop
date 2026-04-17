<div class="flex min-h-screen items-center justify-center">
    <div class="w-full max-w-md space-y-6 p-6">
        <div class="text-center">
            <flux:heading size="xl">Sign In</flux:heading>
            <flux:text class="mt-2">Sign in to your account</flux:text>
        </div>

        <form wire:submit="login" class="space-y-4">
            <flux:input
                wire:model="email"
                label="Email"
                type="email"
                placeholder="you@example.com"
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

        <div class="text-center">
            <flux:text>
                Don't have an account?
                <a href="/account/register" class="text-blue-600 hover:underline">Register</a>
            </flux:text>
        </div>
    </div>
</div>
