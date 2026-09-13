<?php

namespace App\Events;

use App\Models\Fulfillment;

class FulfillmentCreated
{
    public function __construct(public Fulfillment $fulfillment) {}
}
