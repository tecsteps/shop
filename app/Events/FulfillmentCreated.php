<?php

namespace App\Events;

use App\Models\Fulfillment;
use Illuminate\Foundation\Events\Dispatchable;

class FulfillmentCreated
{
    use Dispatchable;

    public function __construct(public readonly Fulfillment $fulfillment) {}
}
