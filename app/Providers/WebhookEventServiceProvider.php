<?php

namespace App\Providers;

use App\Events\CheckoutCompleted;
use App\Events\FulfillmentDelivered;
use App\Events\FulfillmentShipped;
use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Events\OrderFulfilled;
use App\Events\OrderPaid;
use App\Events\OrderRefunded;
use App\Events\ProductCreated;
use App\Events\ProductDeleted;
use App\Events\ProductStatusChanged;
use App\Events\ProductUpdated;
use App\Listeners\DispatchWebhooks;
use App\Listeners\SendCustomerOrderNotifications;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class WebhookEventServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Event::listen(OrderCreated::class, DispatchWebhooks::class.'@orderCreated');
        Event::listen(OrderPaid::class, DispatchWebhooks::class.'@orderPaid');
        Event::listen(CheckoutCompleted::class, DispatchWebhooks::class.'@checkoutCompleted');
        Event::listen(OrderFulfilled::class, DispatchWebhooks::class.'@orderFulfilled');
        Event::listen(OrderCancelled::class, DispatchWebhooks::class.'@orderCancelled');
        Event::listen(OrderRefunded::class, DispatchWebhooks::class.'@orderRefunded');
        Event::listen(ProductCreated::class, DispatchWebhooks::class.'@productCreated');
        Event::listen(ProductUpdated::class, DispatchWebhooks::class.'@productUpdated');
        Event::listen(ProductDeleted::class, DispatchWebhooks::class.'@productDeleted');
        Event::listen(ProductStatusChanged::class, DispatchWebhooks::class.'@productStatusChanged');
        Event::listen(FulfillmentShipped::class, DispatchWebhooks::class.'@fulfillmentShipped');
        Event::listen(FulfillmentDelivered::class, DispatchWebhooks::class.'@fulfillmentDelivered');

        Event::listen(OrderCreated::class, SendCustomerOrderNotifications::class.'@orderCreated');
        Event::listen(OrderRefunded::class, SendCustomerOrderNotifications::class.'@orderRefunded');
        Event::listen(FulfillmentShipped::class, SendCustomerOrderNotifications::class.'@fulfillmentShipped');
        Event::listen(OrderCancelled::class, SendCustomerOrderNotifications::class.'@orderCancelled');
    }
}
