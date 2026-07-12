<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-8">
    <h1 class="text-2xl font-semibold tracking-tight">Reset your password</h1>
    <p class="mt-2 text-sm text-slate-500">Enter your email and we’ll send reset instructions if an account exists.</p>
    @if($sent)
        <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-300">If that email exists, we sent a reset link.</div>
    @else
        <form wire:submit="sendResetLink" class="mt-6 space-y-5">
            <flux:input wire:model="email" label="Email address" type="email" autocomplete="email" required autofocus />
            <flux:button type="submit" variant="primary" class="w-full" wire:loading.attr="disabled">Send reset link</flux:button>
        </form>
    @endif
    <a href="{{ url('/admin/login') }}" wire:navigate class="mt-6 inline-flex text-sm font-medium text-blue-700 hover:underline dark:text-blue-300">Back to sign in</a>
</section>
