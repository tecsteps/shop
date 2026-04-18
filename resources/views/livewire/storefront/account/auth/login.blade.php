<div class="mx-auto max-w-md p-8">
    <flux:heading size="xl">Sign in</flux:heading>

    <form wire:submit="login" class="mt-6 space-y-4">
        <flux:field>
            <flux:label>Email</flux:label>
            <flux:input wire:model="email" type="email" required autocomplete="email" autofocus />
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

        <flux:text class="text-center">
            No account yet? <a href="{{ route('account.register') }}" class="underline">Create one</a>
        </flux:text>
    </form>
</div>
