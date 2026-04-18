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

Schedule::job(new ExpireAbandonedCheckouts)->everyFifteenMinutes()->name('expire-abandoned-checkouts');
Schedule::job(new CleanupAbandonedCarts)->daily()->name('cleanup-abandoned-carts');
Schedule::job(new CancelUnpaidBankTransferOrders)->daily()->name('cancel-unpaid-bank-transfer-orders');
Schedule::job(new AggregateAnalytics)->dailyAt('01:00')->name('aggregate-analytics');
