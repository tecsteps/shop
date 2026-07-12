<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-8" aria-labelledby="login-heading">
    <h1 id="login-heading" class="text-2xl font-semibold tracking-tight">Sign in to your store</h1>
    <p class="mt-2 text-sm text-slate-500">Manage products, orders, customers, and your storefront.</p>
    @if(session('status'))<div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-300">{{ session('status') }}</div>@endif
    <form wire:submit="authenticate" class="mt-6 space-y-5">
        <flux:input wire:model="email" label="Email address" type="email" autocomplete="username" required autofocus />
        <flux:input wire:model="password" label="Password" type="password" autocomplete="current-password" viewable required />
        <div class="flex items-center justify-between gap-4">
            <flux:checkbox wire:model="remember" label="Remember me" />
            <a href="{{ url('/admin/forgot-password') }}" wire:navigate class="text-sm font-medium text-blue-700 hover:underline dark:text-blue-300">Forgot password?</a>
        </div>
        <flux:button type="submit" variant="primary" class="w-full" wire:loading.attr="disabled" wire:target="authenticate">
            <span wire:loading.remove wire:target="authenticate">Sign in</span><span wire:loading wire:target="authenticate">Signing in…</span>
        </flux:button>
    </form>
</section>
