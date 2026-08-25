<x-layouts.auth>
    <x-auth-card title="Admin Login">
        <form method="POST" action="{{ route('admin.login') }}" class="space-y-4">
            @csrf
            <flux:input label="Email" type="email" name="email" required autofocus />
            <flux:input label="Password" type="password" name="password" required />
            <flux:checkbox name="remember" label="Remember me" />
            @error('email')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
            <flux:button type="submit" variant="primary" class="w-full">Login</flux:button>
        </form>
    </x-auth-card>
</x-layouts.auth>
