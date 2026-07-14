<?php

namespace App\Events;

use App\Models\Fulfillment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class FulfillmentShipped
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Fulfillment $fulfillment,
        public readonly bool $notifyCustomer = true,
    ) {}
}
