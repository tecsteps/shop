<?php

use App\Jobs\AggregateAnalytics;
use App\Jobs\CancelUnpaidBankTransferOrders;
use App\Jobs\CleanupAbandonedCarts;
use App\Jobs\ExpireAbandonedCheckouts;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Expire stale checkouts (spec 05 §6.2) and abandon inactive carts (spec 05 §4.5).
Schedule::job(new ExpireAbandonedCheckouts)->everyFifteenMinutes();
Schedule::job(new CleanupAbandonedCarts)->daily();

// Cancel bank transfer orders that remain unpaid (spec 05 §10.8).
Schedule::job(new CancelUnpaidBankTransferOrders)->daily();

// Roll up the previous day's raw analytics events into daily aggregates
// (spec 05 §14.2).
Schedule::job(new AggregateAnalytics)->dailyAt('01:00');
