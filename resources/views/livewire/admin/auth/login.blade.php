<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Sign in')" :description="__('Enter your email and password below to log in')" />

    <x-auth-session-status class="text-center" :status="session('status')" />

    <form method="POST" action="{{ route('admin.login.attempt') }}" class="flex flex-col gap-6" novalidate>
        @csrf

        <flux:input
            name="email"
            :label="__('Email address')"
            :value="old('email')"
            type="email"
            required
            autofocus
            autocomplete="email"
            placeholder="email@example.com"
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
            :placeholder="__('Password')"
            viewable
        />

        @error('password')
            <flux:text class="text-red-600 dark:text-red-400">{{ $message }}</flux:text>
        @enderror

        <flux:checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" />

        <flux:button variant="primary" type="submit" class="w-full" data-test="admin-login-button">
            {{ __('Sign in') }}
        </flux:button>
    </form>
</div>
