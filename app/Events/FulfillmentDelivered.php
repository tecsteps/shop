<?php

namespace App\Events;

use App\Models\Fulfillment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched when a fulfillment is marked as delivered.
 */
class FulfillmentDelivered
{
    use Dispatchable, SerializesModels;

    public function __construct(public Fulfillment $fulfillment) {}
}
