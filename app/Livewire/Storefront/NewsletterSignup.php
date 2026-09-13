<?php

namespace App\Livewire\Storefront;

use Livewire\Component;

class NewsletterSignup extends Component
{
    public string $email = '';

    public bool $subscribed = false;

    public function subscribe(): void
    {
        $this->validate(['email' => 'required|email|max:255']);

        $this->subscribed = true;
    }

    public function render()
    {
        return view('livewire.storefront.newsletter-signup');
    }
}
