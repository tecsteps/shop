<div class="sf-container sf-page-y">
    <div class="mx-auto max-w-md">
        <header class="text-center"><p class="sf-eyebrow">Welcome back</p><h1 class="mt-2 text-3xl font-semibold tracking-tight">Log in to your account</h1></header>
        <div class="sf-card mt-8">
            @error('credentials')<div class="sf-callout sf-callout-error mb-5" role="alert">{{ $message }}</div>@enderror
            <form wire:submit="login" class="space-y-5">
                <div><label for="customer-email" class="sf-label">Email address</label><input id="customer-email" name="email" type="email" wire:model="email" autocomplete="email" required autofocus class="sf-input mt-1 w-full @error('email') sf-input-error @enderror" @error('email') aria-invalid="true" aria-describedby="customer-email-error" @enderror>@error('email')<p id="customer-email-error" class="sf-field-error">{{ $message }}</p>@enderror</div>
                <div><div class="flex items-center justify-between"><label for="customer-password" class="sf-label">Password</label><a href="{{ url('/forgot-password') }}" wire:navigate class="sf-text-link text-sm">Forgot password?</a></div><input id="customer-password" name="password" type="password" wire:model="password" autocomplete="current-password" required class="sf-input mt-1 w-full @error('password') sf-input-error @enderror">@error('password')<p class="sf-field-error">{{ $message }}</p>@enderror</div>
                <button class="sf-button sf-button-primary min-h-12 w-full" wire:loading.attr="disabled" wire:target="login"><span wire:loading.remove wire:target="login">Log in</span><span wire:loading wire:target="login">Logging in...</span></button>
            </form>
        </div>
        <p class="mt-6 text-center text-sm text-slate-600 dark:text-slate-300">Don’t have an account? <a href="{{ url('/account/register') }}" wire:navigate class="sf-text-link">Create one</a></p>
    </div>
</div>
