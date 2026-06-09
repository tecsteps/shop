<div class="flex flex-col gap-6">
    <h1 class="text-xl font-semibold">{{ __('Login') }}</h1>

    <form method="POST" action="{{ route('storefront.account.login.attempt') }}" class="flex flex-col gap-4">
        @csrf

        <flux:input
            name="email"
            :label="__('Email address')"
            :value="old('email')"
            type="email"
            required
            autofocus
            autocomplete="email"
        />

        @error('email')
            <flux:text class="text-red-600 dark:text-red-400">{{ $message }}</flux:text>
        @enderror

        <flux:input
            name="password"
            :label="__('Password')"
            type="password"
            required
            autocomplete="current-password"
        />

        <flux:button variant="primary" type="submit" class="w-full" data-test="customer-login-button">
            {{ __('Login') }}
        </flux:button>
    </form>

    <p class="text-sm text-zinc-600 dark:text-zinc-400">
        {{ __('No account yet?') }}
        <a href="{{ route('storefront.account.register') }}" class="underline">{{ __('Register') }}</a>
    </p>
</div>
