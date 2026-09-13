<?php

namespace App\Events;

use App\Models\Fulfillment;

class FulfillmentShipped
{
    public function __construct(public Fulfillment $fulfillment) {}
}
