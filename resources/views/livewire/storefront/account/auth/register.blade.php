<div class="max-w-md mx-auto py-12 px-4">
    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white mb-8 text-center">Create Account</h1>

    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-8">
        <form wire:submit="register" class="space-y-6">
            <flux:input
                wire:model="name"
                label="Name"
                type="text"
                placeholder="Your name"
                required
                autofocus
            />

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

            <flux:checkbox
                wire:model="marketingOptIn"
                label="I'd like to receive marketing emails and updates"
            />

            @error('email')
                <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror

            <flux:button type="submit" variant="primary" class="w-full">
                Create Account
            </flux:button>
        </form>

        <p class="mt-6 text-center text-sm text-zinc-600 dark:text-zinc-400">
            Already have an account?
            <a href="{{ route('customer.login') }}" class="text-blue-600 hover:underline dark:text-blue-400" wire:navigate>
                Sign in
            </a>
        </p>
    </div>
</div>
