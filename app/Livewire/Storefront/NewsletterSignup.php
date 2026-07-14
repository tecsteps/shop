<?php

namespace App\Livewire\Storefront;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class NewsletterSignup extends StorefrontComponent
{
    public string $email = '';

    public bool $subscribed = false;

    public function subscribe(): void
    {
        $this->validate(['email' => ['required', 'email:rfc', 'max:255']]);

        $key = 'newsletter:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('email', 'Please wait before trying again.');

            return;
        }

        RateLimiter::hit($key, 60);
        $this->subscribed = true;
        $this->reset('email');
    }

    public function render(): View
    {
        return view('storefront.components.newsletter-signup');
    }
}
