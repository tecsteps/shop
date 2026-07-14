<?php

namespace App\Events;

use App\Models\Cart;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class CartUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Cart $cart) {}
}
