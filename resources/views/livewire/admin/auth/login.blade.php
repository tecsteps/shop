<flux:card>
    <div class="mb-6 text-center">
        <flux:heading size="lg" level="1">Log in</flux:heading>
        <flux:text class="mt-1">Sign in to your admin account</flux:text>
    </div>

    @if (session('status'))
        <flux:callout variant="success" class="mb-4">
            <flux:callout.text>{{ session('status') }}</flux:callout.text>
        </flux:callout>
    @endif

    @if ($errorMessage)
        <flux:callout variant="danger" class="mb-4">
            <flux:callout.text>{{ $errorMessage }}</flux:callout.text>
        </flux:callout>
    @endif

    <form wire:submit="login" class="space-y-4">
        <flux:field>
            <flux:label for="email">Email</flux:label>
            <flux:input id="email" type="email" wire:model="email" autocomplete="email" autofocus />
            <flux:error name="email" />
        </flux:field>

        <flux:field>
            <flux:label for="password">Password</flux:label>
            <flux:input id="password" type="password" wire:model="password" autocomplete="current-password" />
            <flux:error name="password" />
        </flux:field>

        <div class="flex items-center justify-between">
            <flux:checkbox wire:model="remember" label="Remember me" />
            <flux:link href="{{ route('admin.password.request') }}" variant="subtle">Forgot password?</flux:link>
        </div>

        <flux:button type="submit" variant="primary" class="w-full">Log in</flux:button>
    </form>
</flux:card>
