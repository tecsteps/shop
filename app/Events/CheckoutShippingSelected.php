<?php

namespace App\Events;

use App\Models\Checkout;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched when a shipping method is selected (or skipped for digital-only carts).
 */
class CheckoutShippingSelected
{
    use Dispatchable, SerializesModels;

    public function __construct(public Checkout $checkout) {}
}
