<div class="mx-auto max-w-md px-4 py-16 sm:px-6">
    <h1 class="text-center text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Log in to your account</h1>

    @if (session('status'))
        <flux:callout variant="success" class="mt-6">
            <flux:callout.text>{{ session('status') }}</flux:callout.text>
        </flux:callout>
    @endif

    @if ($errorMessage)
        <flux:callout variant="danger" class="mt-6">
            <flux:callout.text>{{ $errorMessage }}</flux:callout.text>
        </flux:callout>
    @endif

    <form wire:submit="login" class="mt-8 space-y-5">
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
            <flux:link href="{{ route('storefront.password.request') }}" variant="subtle" class="text-sm">Forgot password?</flux:link>
        </div>

        <flux:button type="submit" variant="primary" class="w-full">Log in</flux:button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-600 dark:text-gray-400">
        Don't have an account?
        <flux:link href="{{ route('storefront.account.register') }}">Create one</flux:link>
    </p>
</div>
