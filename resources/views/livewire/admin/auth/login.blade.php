<div>
    <p class="text-sm font-semibold uppercase tracking-widest text-blue-600">Acme Fashion</p>
    <h1 class="mt-2 text-3xl font-bold">Sign in</h1>
    <p class="mt-2 text-sm text-zinc-600">Admin access</p>
    <form wire:submit="login" class="mt-8 space-y-5">
        <div><label for="admin-email" class="text-sm font-medium">Email</label><input id="admin-email" type="email" autocomplete="email" wire:model="email" class="mt-2 w-full rounded-lg border border-zinc-300 px-4 py-3">@error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
        <div><label for="admin-password" class="text-sm font-medium">Password</label><input id="admin-password" type="password" autocomplete="current-password" wire:model="password" class="mt-2 w-full rounded-lg border border-zinc-300 px-4 py-3">@error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="remember"> Remember me</label>
        <button class="w-full rounded-full bg-zinc-900 px-5 py-3 font-semibold text-white">Sign in</button>
    </form>
    <a href="{{ route('admin.password.request') }}" class="mt-5 block text-center text-sm text-blue-600 underline">Forgot password?</a>
</div>
