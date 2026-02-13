<div class="flex min-h-screen items-center justify-center">
    <div class="w-full max-w-md space-y-6 p-6">
        <div class="text-center">
            <flux:heading size="xl">Create Account</flux:heading>
            <flux:text class="mt-2">Register for a new account</flux:text>
        </div>

        <form wire:submit="register" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <flux:input
                    wire:model="first_name"
                    label="First Name"
                    type="text"
                    required
                />

                <flux:input
                    wire:model="last_name"
                    label="Last Name"
                    type="text"
                    required
                />
            </div>

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

            <flux:input
                wire:model="password_confirmation"
                label="Confirm Password"
                type="password"
                required
            />

            <flux:button type="submit" variant="primary" class="w-full">
                Create Account
            </flux:button>
        </form>

        <div class="text-center">
            <flux:text>
                Already have an account?
                <a href="/account/login" class="text-blue-600 hover:underline">Sign in</a>
            </flux:text>
        </div>
    </div>
</div>
