<?php

namespace App\Events;

use App\Models\Checkout;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class CheckoutAddressed
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Checkout $checkout) {}
}
