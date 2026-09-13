<?php

namespace App\Events;

use App\Models\Fulfillment;

class FulfillmentDelivered
{
    public function __construct(public Fulfillment $fulfillment) {}
}
