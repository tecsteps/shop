<?php

namespace App\Events;

use App\Models\Checkout;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched when a contact email and shipping address are saved on a checkout.
 */
class CheckoutAddressed
{
    use Dispatchable, SerializesModels;

    public function __construct(public Checkout $checkout) {}
}
