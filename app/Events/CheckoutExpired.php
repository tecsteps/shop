<?php

namespace App\Events;

use App\Models\Checkout;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched when an idle checkout expires and reserved inventory is released.
 */
class CheckoutExpired
{
    use Dispatchable, SerializesModels;

    public function __construct(public Checkout $checkout) {}
}
