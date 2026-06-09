<div class="flex flex-col gap-6">
    <h1 class="text-xl font-semibold">{{ __('Register') }}</h1>

    <form method="POST" action="{{ route('storefront.account.register.attempt') }}" class="flex flex-col gap-4">
        @csrf

        <flux:input
            name="name"
            :label="__('Name')"
            :value="old('name')"
            type="text"
            required
            autofocus
            autocomplete="name"
        />

        <flux:input
            name="email"
            :label="__('Email address')"
            :value="old('email')"
            type="email"
            required
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
            autocomplete="new-password"
        />

        <flux:input
            name="password_confirmation"
            :label="__('Confirm password')"
            type="password"
            required
            autocomplete="new-password"
        />

        <flux:checkbox name="marketing_opt_in" :label="__('Send me product news and offers')" />

        <flux:button variant="primary" type="submit" class="w-full" data-test="customer-register-button">
            {{ __('Register') }}
        </flux:button>
    </form>
</div>
