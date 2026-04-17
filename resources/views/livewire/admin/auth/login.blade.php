<div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-8">
    <flux:heading size="lg" class="mb-6">Sign in to Admin</flux:heading>

    <form wire:submit="login" class="space-y-6">
        <flux:input
            wire:model="email"
            label="Email"
            type="email"
            placeholder="admin@example.com"
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
</div>
