<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Commerce Timeouts
    |--------------------------------------------------------------------------
    |
    | These values drive the scheduled commerce maintenance jobs. They are
    | sensible defaults; per-store overrides live in store_settings.settings_json
    | (for example `bank_transfer_cancel_days`).
    |
    */

    // Days a guest/customer cart may sit idle (status = active) before the
    // CleanupAbandonedCarts job marks it abandoned.
    'abandoned_cart_days' => 14,

    // Hours an in-progress checkout may sit idle before ExpireAbandonedCheckouts
    // expires it and releases any reserved inventory.
    'checkout_expiry_hours' => 24,

    // Days a pending bank-transfer order may remain unpaid before
    // CancelUnpaidBankTransferOrders cancels it and releases reserved inventory.
    'bank_transfer_cancel_days' => 7,

    /*
    |--------------------------------------------------------------------------
    | Order Numbering
    |--------------------------------------------------------------------------
    */

    // First order number assigned per store.
    'order_number_start' => 1001,

    // Default prefix for human-readable order numbers (overridable per store via
    // store_settings key `order_number_prefix`).
    'order_number_prefix' => '#',

    /*
    |--------------------------------------------------------------------------
    | Bank Transfer Instructions
    |--------------------------------------------------------------------------
    |
    | Static mock bank details shown on bank-transfer order confirmations. No
    | real bank interaction occurs.
    |
    */

    'bank_transfer' => [
        'bank_name' => 'Mock Bank AG',
        'iban' => 'DE89 3704 0044 0532 0130 00',
        'bic' => 'COBADEFFXXX',
    ],

];
