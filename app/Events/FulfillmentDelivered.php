<?php

namespace App\Events;

use App\Models\Fulfillment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FulfillmentDelivered
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Fulfillment $fulfillment,
    ) {}
}
