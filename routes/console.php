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

Schedule::job(new ExpireAbandonedCheckouts)->everyFifteenMinutes();
Schedule::job(new CleanupAbandonedCarts)->dailyAt('03:00');
Schedule::job(new CancelUnpaidBankTransferOrders)->dailyAt('04:00');
Schedule::job(new AggregateAnalytics)->dailyAt('02:00');
