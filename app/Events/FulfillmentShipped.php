<?php

namespace App\Events;

use App\Models\Fulfillment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FulfillmentShipped
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Fulfillment $fulfillment,
    ) {}
}
