<?php

namespace App\Providers;

use App\Events\CustomerCreated;
use App\Events\FulfillmentCreated;
use App\Events\FulfillmentShipped;
use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Events\OrderFulfilled;
use App\Events\OrderPaid;
use App\Events\OrderRefunded;
use App\Listeners\DispatchWebhooks;
use App\Models\Customer;
use App\Observers\CustomerObserver;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Customer::observe(CustomerObserver::class);

        Event::listen(OrderCreated::class, [DispatchWebhooks::class, 'handleOrderCreated']);
        Event::listen(OrderPaid::class, [DispatchWebhooks::class, 'handleOrderPaid']);
        Event::listen(OrderCancelled::class, [DispatchWebhooks::class, 'handleOrderCancelled']);
        Event::listen(OrderRefunded::class, [DispatchWebhooks::class, 'handleOrderRefunded']);
        Event::listen(OrderFulfilled::class, [DispatchWebhooks::class, 'handleOrderFulfilled']);
        Event::listen(FulfillmentCreated::class, [DispatchWebhooks::class, 'handleFulfillmentCreated']);
        Event::listen(FulfillmentShipped::class, [DispatchWebhooks::class, 'handleFulfillmentShipped']);
        Event::listen(CustomerCreated::class, [DispatchWebhooks::class, 'handleCustomerCreated']);
    }
}
