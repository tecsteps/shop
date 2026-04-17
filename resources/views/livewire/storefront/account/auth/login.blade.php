<div class="max-w-md mx-auto py-12 px-4">
    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white mb-8 text-center">Sign In</h1>

    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-8">
        <form wire:submit="login" class="space-y-6">
            <flux:input
                wire:model="email"
                label="Email"
                type="email"
                placeholder="you@example.com"
                required
                autofocus
            />

            <flux:input
                wire:model="password"
                label="Password"
                type="password"
                required
            />

            <flux:checkbox
                wire:model="remember"
                label="Remember me"
            />

            @error('email')
                <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror

            <flux:button type="submit" variant="primary" class="w-full">
                Sign in
            </flux:button>
        </form>

        <p class="mt-6 text-center text-sm text-zinc-600 dark:text-zinc-400">
            Don't have an account?
            <a href="{{ route('customer.register') }}" class="text-blue-600 hover:underline dark:text-blue-400" wire:navigate>
                Create one
            </a>
        </p>
    </div>
</div>
