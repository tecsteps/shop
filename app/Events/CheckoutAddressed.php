<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CheckoutAddressed
{
    use Dispatchable, SerializesModels;

    public function __construct(public $model) {}
}
